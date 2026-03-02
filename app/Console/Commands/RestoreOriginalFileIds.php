<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class RestoreOriginalFileIds extends Command
{
    protected $signature = 'videos:restore-original-ids
                            {--dry-run : Solo muestra qué cambiaría, sin modificar la DB}';

    protected $description = 'Restaura los telegram_file_id originales de @XshopVideobot en todos los videos importados';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY-RUN] No se modificará la base de datos.');
        }

        $data = $this->originalData();
        $updated = 0;
        $notFound = 0;

        foreach ($data as $row) {
            $video = Video::where('created_at', $row['created_at'])->first();

            if (!$video) {
                $this->warn("No encontrado: created_at={$row['created_at']} title={$row['title']}");
                $notFound++;
                continue;
            }

            if ($video->telegram_file_id === $row['telegram_file_id']) {
                continue; // Ya tiene el ID correcto
            }

            $this->line("Actualizando ID {$video->id} ({$video->title})");

            if (!$dryRun) {
                $video->telegram_file_id = $row['telegram_file_id'];
                $video->save();
            }

            $updated++;
        }

        $this->info("Completado — Actualizados: {$updated} | No encontrados: {$notFound}");

        return 0;
    }

    private function originalData(): array
    {
        return [
            ['created_at' => '2025-07-04 21:55:57', 'title' => 'LaRusa',                          'telegram_file_id' => 'BAACAgQAAxkBAAMXaGhN7JqogH98iAw1qiH6BVu8HYgAAlodAAJdPUFTZgnM71TYcNg2BA'],
            ['created_at' => '2025-07-06 10:34:10', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMcaGpRIuzrpCKPs1lsEBWOQn1bkB8AAu4YAALcLlhTrxh3TpMKE442BA'],
            ['created_at' => '2025-07-06 20:36:09', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMmaGreOLG3JFZbX6tKJcm8HWoHpRcAAgYaAALcLlhTk9OeDfSkjSk2BA'],
            ['created_at' => '2025-07-06 20:36:48', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMoaGreYHjm254W0cDyQQ37KH1OoKgAAgcaAALcLlhTdS70pzGuzG02BA'],
            ['created_at' => '2025-07-06 20:46:10', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMraGrgkvuIt4eqp-FoqdRgb3b4ArMAAgoaAALcLlhTAZZTZViu9SE2BA'],
            ['created_at' => '2025-07-06 20:55:51', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMuaGri1qjyDvU8krXel9hOhBlsU_MAAhAaAALcLlhTtFAWBrN--4M2BA'],
            ['created_at' => '2025-07-07 14:51:26', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMwaGve7GB5_pvFvV2YNprW4ncb-mcAAp0ZAALcLmBT8lq-461tae42BA'],
            ['created_at' => '2025-07-07 14:51:27', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAMxaGve7KzuBrt1DRszJxRcwgJySsYAAp4ZAALcLmBTlf1xN49tZ0I2BA'],
            ['created_at' => '2025-07-07 15:00:32', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAM2aGvhEJW6s6wS5n43jBET7qPZYswAAqIZAALcLmBTh5Ap1KX-6lc2BA'],
            ['created_at' => '2025-07-07 15:04:14', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAM4aGvh7UvOxjsO_zywGAFIQx9O8TwAAqYZAALcLmBTd2mQHxDuAAHoNgQ'],
            ['created_at' => '2025-07-07 15:06:36', 'title' => 'Prvega',                          'telegram_file_id' => 'BAACAgQAAxkBAAM6aGvifMSuX6JQ4DKySg97Gib17DcAArUZAALcLmBTsjcado63Hss2BA'],
            ['created_at' => '2025-07-07 15:46:23', 'title' => 'Mellamanmimii',                   'telegram_file_id' => 'BAACAgQAAxkBAAM8aGvrzQXZ766dWyutHBEGVji4l84AAucZAALcLmBT68I_6pRm8Sg2BA'],
            ['created_at' => '2025-07-07 20:27:51', 'title' => 'Mellamanmimii',                   'telegram_file_id' => 'BAACAgQAAxkBAANAaGwtxpd85CxFkk7_iljmV1QFvbkAAhwbAAIrumFTIYKBlyMwsuY2BA'],
            ['created_at' => '2025-10-20 12:30:36', 'title' => 'Miaux98 Recopilación',            'telegram_file_id' => 'BAACAgQAAxkBAANyaPYra8Qav3CjQzeeBe_--jSBtbUAAnApAALzqblTBl2Fsi4gsAk2BA'],
            ['created_at' => '2025-11-02 22:26:52', 'title' => 'larusadecarabanchel follando',    'telegram_file_id' => 'BAACAgQAAxkBAAN2aQfaq8IhoSQKPgVTyD5xCUcn7D4AAjshAALuKUFQuzoTRbwKBr42BA'],
            ['created_at' => '2025-11-02 22:29:39', 'title' => 'larusadecarabanchel tocandose',   'telegram_file_id' => 'BAACAgQAAxkBAAN4aQfbUhtl8M-W7TZcurCfnsTknOIAAjwhAALuKUFQes5SzsVf8EY2BA'],
            ['created_at' => '2025-11-02 22:31:59', 'title' => 'larusadecarabanchel Directo old school', 'telegram_file_id' => 'BAACAgQAAxkBAAN6aQfb3n2vvluec0UgJrRcsnZiJ_8AAj0hAALuKUFQIwN436GGIQw2BA'],
            ['created_at' => '2025-11-02 22:34:15', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAN8aQfcZsBGnjB60_oM_JSnP2vaBg4AAj4hAALuKUFQylnuGdm_lms2BA'],
            ['created_at' => '2025-11-02 22:34:16', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAN9aQfcZsEiShOPz8N6KGAMHcS7mx0AAj8hAALuKUFQPPplvHa1ErE2BA'],
            ['created_at' => '2025-11-02 22:34:17', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAN_aQfcZys62tOghadeG9gfhriKuIAAAkAhAALuKUFQEoI6e4POjyw2BA'],
            ['created_at' => '2025-11-02 22:34:18', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOBaQfcZ5UDowL_nPFw5zwLJfqfankAAkEhAALuKUFQXqUW9sKBv7I2BA'],
            ['created_at' => '2025-11-02 22:34:19', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOCaQfcaJAvJzeuOOrYFEc24Q1XbjoAAkIhAALuKUFQsuG3-EmqRZc2BA'],
            ['created_at' => '2025-11-02 22:34:20', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOEaQfcad1EFAxOSOI0AAFLjidurk7fAAJDIQAC7ilBULuvObmIh4M9NgQ'],
            ['created_at' => '2025-11-02 22:34:21', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOHaQfcakd8USRZ6OqLjjSDRTy_SqoAAkQhAALuKUFQ-81_qUD8yUM2BA'],
            ['created_at' => '2025-11-02 22:34:22', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOIaQfcax9Ub0_SmhYd7DpsODiBb6sAAkUhAALuKUFQI2p499TTpgI2BA'],
            ['created_at' => '2025-11-02 22:34:23', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOKaQfca7kYHojCQAw3-F7ulLE8haQAAkYhAALuKUFQQmP2svgm_8U2BA'],
            ['created_at' => '2025-11-02 22:34:24', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOMaQfcbam-TD90Nt7Fe558hrB3rD8AAkchAALuKUFQ77InWUhtbSY2BA'],
            ['created_at' => '2025-11-02 22:34:25', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAONaQfcbXX2n8-zpq4Q6I38he-atFcAAkghAALuKUFQ4F7ogBYAAY2lNgQ'],
            ['created_at' => '2025-11-02 22:34:26', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOOaQfcbXuijqrq8m-2ZMpHJPaigQoAAkkhAALuKUFQ4TIAATg77XMTNgQ'],
            ['created_at' => '2025-11-02 22:34:27', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAORaQfccBtGsh6_AUrVLe79Z4TmrWAAAkohAALuKUFQ1etVW6HFOIc2BA'],
            ['created_at' => '2025-11-02 22:34:28', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOUaQfccqH-Z6_3sHcsCpDqkF_lDaAAAkshAALuKUFQ3acJqfeniGI2BA'],
            ['created_at' => '2025-11-02 22:34:29', 'title' => 'LaRusa de Carabanchel',           'telegram_file_id' => 'BAACAgQAAxkBAAOXaQfccyyI66-dDNRfqxoyrTbq3mkAAkwhAALuKUFQYXrvrPjlSEI2BA'],
            ['created_at' => '2025-11-02 22:34:31', 'title' => 'larusadecarabanchel',             'telegram_file_id' => 'BAACAgQAAxkBAAOaaQfcdqIwNbWOxsnIeG71slMruSMAAk0hAALuKUFQco7Di3FQQYQ2BA'],
            ['created_at' => '2025-11-16 15:33:01', 'title' => 'Mami Lechera',                    'telegram_file_id' => 'BAACAgQAAxkBAAIBamkZ7qzOjj4z5WIksK1ZRonAHTnJAAIwHAACsLLQUN4ivFE5KFTeNgQ'],
            ['created_at' => '2025-11-16 15:33:02', 'title' => 'Mami Lechera',                    'telegram_file_id' => 'BAACAgQAAxkBAAIBa2kZ7qxIUsApNyDQ9_ranymgkpL_AAIxHAACsLLQUERUSbAfRuwSNgQ'],
            ['created_at' => '2025-11-16 15:33:03', 'title' => 'Mami Lechera',                    'telegram_file_id' => 'BAACAgQAAxkBAAIBbGkZ7qyjyKtvWSeOku_kAQn7eP2tAAIyHAACsLLQUGvl7NZ8C9OuNgQ'],
            ['created_at' => '2025-11-16 15:33:04', 'title' => 'Mami Lechera',                    'telegram_file_id' => 'BAACAgQAAxkBAAIBbWkZ7qzK9HB9D1c8yolaFGKcbyouAAIzHAACsLLQUB9QsPkTrbAXNgQ'],
            ['created_at' => '2025-11-16 15:33:05', 'title' => 'Mami Lechera',                    'telegram_file_id' => 'BAACAgQAAxkBAAIBbmkZ7qzQX0iYCyv0kn2WmVTwTmLVAAI0HAACsLLQUJekfMPyYQIHNgQ'],
            ['created_at' => '2025-11-16 15:33:06', 'title' => 'Mami Lechera',                    'telegram_file_id' => 'BAACAgQAAxkBAAIBb2kZ7qyg4E-YZJGyCjOX72yI9Vb7AAI1HAACsLLQUGkGO52rXnEpNgQ'],
            ['created_at' => '2026-02-20 22:04:16', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFRmmY2l3baq-fbpJ9EDyI0QGrvBirAAL6HgACS5nJUOpxcIgKxqZuOgQ'],
            ['created_at' => '2026-02-20 22:04:17', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFR2mY2l2TPPslStFXpUIjGT4EbZLuAALwHgACS5nJUFL5zYuDa-eWOgQ'],
            ['created_at' => '2026-02-20 22:04:18', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFSGmY2l1gMAtFS6TyV6-PMear19rxAALxHgACS5nJUHTjljgPoTIYOgQ'],
            ['created_at' => '2026-02-20 22:04:19', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFSWmY2l04bbKyxg1THuo815E2WbEOAALyHgACS5nJUA6L2zayC2jFOgQ'],
            ['created_at' => '2026-02-20 22:04:19', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFSmmY2l0sIxJgbQUitihE0pddzeGBAALzHgACS5nJUNeXu05FYYhSOgQ'],
            ['created_at' => '2026-02-20 22:04:20', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFS2mY2l18jTzQriI6GFHd3uesrkdCAAL0HgACS5nJUFbu67lU6L-eOgQ'],
            ['created_at' => '2026-02-20 22:04:21', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFTGmY2l2t7EI0XBMBLbNQja7u6L5aAAL1HgACS5nJUC_6lwh99derOgQ'],
            ['created_at' => '2026-02-20 22:04:22', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFTWmY2l3RJAVUutOdSKu8um3Y7FWkAAL2HgACS5nJULxDxV_yKAE2OgQ'],
            ['created_at' => '2026-02-20 22:04:23', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFTmmY2l33GvFGts9r8jtnG5dTRooGAAL4HgACS5nJUAcnUxYCsBwIOgQ'],
            ['created_at' => '2026-02-20 22:04:24', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFT2mY2l1q1UwbMWrEOUP8_tsCvYCpAAL3HgACS5nJUAABX7-sAeYybDoE'],
            ['created_at' => '2026-02-20 22:04:25', 'title' => 'Miaux',                           'telegram_file_id' => 'BAACAgQAAxkBAAIFUGmY2l1dFAxlGvhYaDZUKJPl8j7NAAL5HgACS5nJUAWiXP4oCCTIOgQ'],
        ];
    }
}
