<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchVideoMetadata extends Command
{
    protected $signature = 'videos:fetch-metadata
                            {--chat-id= : Telegram chat_id donde enviar temporalmente los videos (por defecto usa el sync user)}
                            {--only-missing : Solo procesar videos sin miniatura Y sin duración}
                            {--limit=50 : Máximo de videos a procesar en una ejecución}';

    protected $description = 'Obtiene duración y miniatura de videos existentes via Telegram API';

    public function handle(): int
    {
        $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');

        if (!$botToken) {
            $this->error('No hay bot token configurado.');
            return 1;
        }

        // Determinar chat_id destino
        $chatId = $this->option('chat-id');
        if (!$chatId) {
            $syncUserId = Setting::get('sync_user_telegram_id');
            if (!$syncUserId) {
                $this->error('No hay --chat-id ni sync user configurado. Usa: --chat-id=TU_TELEGRAM_ID');
                return 1;
            }
            $chatId = $syncUserId;
        }

        $limit = (int) $this->option('limit');

        // Seleccionar videos a procesar
        $query = Video::whereNotNull('telegram_file_id');

        if ($this->option('only-missing')) {
            $query->where(function ($q) {
                $q->whereNull('duration')
                  ->orWhere(function ($q2) {
                      $q2->whereNull('thumbnail_path')
                         ->whereNull('thumbnail_url')
                         ->whereNull('thumbnail_blob_url');
                  });
            });
        } else {
            // Por defecto: solo los que faltan miniatura o duración
            $query->where(function ($q) {
                $q->whereNull('duration')
                  ->orWhere(function ($q2) {
                      $q2->whereNull('thumbnail_path')
                         ->whereNull('thumbnail_url')
                         ->whereNull('thumbnail_blob_url');
                  });
            });
        }

        $videos = $query->limit($limit)->get();

        if ($videos->isEmpty()) {
            $this->info('No hay videos que procesar.');
            return 0;
        }

        $this->info("Procesando {$videos->count()} videos...");
        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $updated = 0;
        $failed  = 0;

        foreach ($videos as $video) {
            try {
                // Enviar el video al chat usando su file_id
                $response = Http::timeout(20)->post("https://api.telegram.org/bot{$botToken}/sendVideo", [
                    'chat_id' => $chatId,
                    'video'   => $video->telegram_file_id,
                    'caption' => "🔄 Procesando metadatos [ID:{$video->id}]",
                ]);

                if (!$response->successful() || !$response->json('ok')) {
                    $this->newLine();
                    $this->warn("Video {$video->id}: fallo al enviar — " . $response->json('description', 'error desconocido'));
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $result    = $response->json('result');
                $tgVideo   = $result['video'] ?? null;
                $messageId = $result['message_id'] ?? null;

                if (!$tgVideo) {
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $updates = [];

                // Guardar duración si falta
                if (empty($video->duration) && !empty($tgVideo['duration'])) {
                    $updates['duration'] = $tgVideo['duration'];
                }

                // Guardar file_size si falta
                if (empty($video->file_size) && !empty($tgVideo['file_size'])) {
                    $updates['file_size'] = $tgVideo['file_size'];
                }

                // Descargar miniatura si falta
                $needsThumbnail = empty($video->thumbnail_path)
                               && empty($video->thumbnail_url)
                               && empty($video->thumbnail_blob_url);

                if ($needsThumbnail) {
                    $thumbFileId = $tgVideo['thumbnail']['file_id']
                                ?? $tgVideo['thumb']['file_id']
                                ?? null;

                    if ($thumbFileId) {
                        $thumbnailPath = $this->downloadThumbnail($botToken, $thumbFileId, $video->id);
                        if ($thumbnailPath) {
                            $updates['thumbnail_path'] = $thumbnailPath;
                        }
                    }
                }

                if (!empty($updates)) {
                    $video->update($updates);
                    $updated++;
                }

                // Borrar el mensaje temporal del chat
                if ($messageId) {
                    Http::timeout(5)->post("https://api.telegram.org/bot{$botToken}/deleteMessage", [
                        'chat_id'    => $chatId,
                        'message_id' => $messageId,
                    ]);
                }

                // Pausa pequeña para no saturar la API de Telegram
                usleep(400000); // 0.4s

            } catch (\Exception $e) {
                Log::warning("FetchVideoMetadata: video {$video->id} error: " . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Actualizados: {$updated} | ❌ Fallidos: {$failed}");

        return 0;
    }

    private function downloadThumbnail(string $botToken, string $fileId, int $videoId): ?string
    {
        try {
            $fileInfo = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getFile", [
                'file_id' => $fileId,
            ]);

            if (!$fileInfo->successful() || empty($fileInfo->json('result.file_path'))) {
                return null;
            }

            $filePath      = $fileInfo->json('result.file_path');
            $imageResponse = Http::timeout(15)->get("https://api.telegram.org/file/bot{$botToken}/{$filePath}");

            if (!$imageResponse->successful()) {
                return null;
            }

            $storagePath = "thumbnails/tg_{$videoId}.jpg";
            Storage::disk('public')->put($storagePath, $imageResponse->body());

            return $storagePath;

        } catch (\Exception $e) {
            return null;
        }
    }
}
