<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReregisterVideos extends Command
{
    protected $signature = 'videos:reregister
                            {old_token : Token del bot antiguo que tiene los file_ids}
                            {chat_id : ID del grupo/canal compartido donde el bot antiguo enviará los videos}
                            {--dry-run : Solo simula, no modifica la base de datos}';

    protected $description = 'Migra los file_ids de los videos del bot antiguo al bot nuevo usando un grupo compartido';

    private string $newToken;
    private string $oldToken;
    private string $chatId;
    private bool $dryRun;

    public function handle(): int
    {
        $this->oldToken = $this->argument('old_token');
        $this->chatId   = $this->argument('chat_id');
        $this->dryRun   = $this->option('dry-run');
        $this->newToken = Setting::get('telegram_bot_token');

        if (!$this->newToken) {
            $this->error('No se encontró telegram_bot_token en la configuración.');
            return 1;
        }

        // Verificar ambos bots
        $oldMe = $this->callApi($this->oldToken, 'getMe');
        $newMe = $this->callApi($this->newToken, 'getMe');

        if (!$oldMe['ok'] || !$newMe['ok']) {
            $this->error('No se pudo verificar uno o ambos bots. Revisa los tokens.');
            return 1;
        }

        $this->info("Bot antiguo: @{$oldMe['result']['username']}");
        $this->info("Bot nuevo:   @{$newMe['result']['username']}");

        if ($this->dryRun) {
            $this->warn('[DRY-RUN] No se modificará la base de datos.');
        }

        // Quitar webhook del bot nuevo temporalmente para poder usar getUpdates
        $this->callApi($this->newToken, 'deleteWebhook', ['drop_pending_updates' => true]);
        $this->info('Webhook del bot nuevo eliminado temporalmente.');

        // Avanzar offset para ignorar mensajes viejos
        $offset = $this->advanceOffset();
        $this->info("Offset inicial del bot nuevo: {$offset}");

        // Obtener videos con file_id (del bot antiguo)
        $videos = Video::whereNotNull('telegram_file_id')->get();
        $this->info("Videos a migrar: {$videos->count()}");

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $ok = 0;
        $fail = 0;

        foreach ($videos as $video) {
            $caption = "[vid:{$video->id}]";
            $fileId  = $video->telegram_file_id;
            $type    = $video->video_type ?? 'file';

            // Bot antiguo envía el video al grupo compartido
            $method = match ($type) {
                'video'     => 'sendVideo',
                'animation' => 'sendAnimation',
                default     => 'sendDocument',
            };

            $sent = $this->sendWithRetry($this->oldToken, $method, [
                'chat_id' => $this->chatId,
                'caption' => $caption,
                ($type === 'animation' ? 'animation' : ($type === 'video' ? 'video' : 'document')) => $fileId,
            ]);

            if (!$sent['ok']) {
                // Intentar con sendDocument como fallback
                $sent = $this->sendWithRetry($this->oldToken, 'sendDocument', [
                    'chat_id'  => $this->chatId,
                    'caption'  => $caption,
                    'document' => $fileId,
                ]);
            }

            if (!$sent['ok']) {
                $this->newline();
                $this->warn("Video ID {$video->id}: no se pudo enviar — {$sent['description']}");
                Log::warning("ReregisterVideos: video {$video->id} send failed", $sent);
                $fail++;
                $bar->advance();
                continue;
            }

            // Esperar un poco y leer el mensaje en el bot nuevo
            sleep(1);
            $newFileId   = null;
            $newFileType = null;

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $updates = $this->callApi($this->newToken, 'getUpdates', [
                    'offset'  => $offset,
                    'timeout' => 5,
                    'limit'   => 20,
                ]);

                if (!$updates['ok']) {
                    sleep(1);
                    continue;
                }

                foreach ($updates['result'] as $update) {
                    $offset = $update['update_id'] + 1;
                    $msg    = $update['message'] ?? $update['channel_post'] ?? null;
                    if (!$msg) continue;

                    $msgCaption = $msg['caption'] ?? '';
                    if ($msgCaption !== $caption) continue;

                    // Encontrado — extraer file_id del bot nuevo
                    if (isset($msg['video'])) {
                        $newFileId   = $msg['video']['file_id'];
                        $newFileType = 'video';
                    } elseif (isset($msg['animation'])) {
                        $newFileId   = $msg['animation']['file_id'];
                        $newFileType = 'animation';
                    } elseif (isset($msg['document'])) {
                        $newFileId   = $msg['document']['file_id'];
                        $newFileType = 'document';
                    }
                    break 2;
                }

                sleep(1);
            }

            if ($newFileId) {
                if (!$this->dryRun) {
                    $video->telegram_file_id = $newFileId;
                    $video->video_type       = $newFileType;
                    $video->save();
                }
                $ok++;
            } else {
                $this->newline();
                $this->warn("Video ID {$video->id}: no se recibió respuesta del bot nuevo.");
                Log::warning("ReregisterVideos: video {$video->id} no update received");
                $fail++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newline();

        // Restaurar webhook del bot nuevo
        $webhookUrl = url('/telegram/webhook');
        $this->callApi($this->newToken, 'setWebhook', ['url' => $webhookUrl]);
        $this->info("Webhook restaurado: {$webhookUrl}");

        $this->info("Completado — OK: {$ok} | Fallidos: {$fail}");

        return $fail > 0 ? 1 : 0;
    }

    private function advanceOffset(): int
    {
        // Leer updates pendientes y devolver el siguiente offset
        $updates = $this->callApi($this->newToken, 'getUpdates', ['limit' => 100]);
        if (!$updates['ok'] || empty($updates['result'])) {
            return 0;
        }
        $last = end($updates['result']);
        $offset = $last['update_id'] + 1;

        // Confirmar consumo
        $this->callApi($this->newToken, 'getUpdates', ['offset' => $offset, 'limit' => 1]);

        return $offset;
    }

    private function sendWithRetry(string $token, string $method, array $data, int $maxRetries = 3): array
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $result = $this->callApi($token, $method, $data);
            if ($result['ok']) {
                return $result;
            }
            // Rate limit — esperar el tiempo indicado por Telegram
            if (isset($result['parameters']['retry_after'])) {
                $wait = $result['parameters']['retry_after'] + 1;
                $this->newline();
                $this->info("Rate limit — esperando {$wait}s...");
                sleep($wait);
                continue;
            }
            // Otro error — no reintentar
            return $result;
        }
        return ['ok' => false, 'description' => 'Max retries exceeded'];
    }

    private function callApi(string $token, string $method, array $data = []): array
    {
        try {
            $response = Http::timeout(30)->post(
                "https://api.telegram.org/bot{$token}/{$method}",
                $data
            );
            return $response->json() ?? ['ok' => false, 'description' => 'Invalid JSON'];
        } catch (\Exception $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
