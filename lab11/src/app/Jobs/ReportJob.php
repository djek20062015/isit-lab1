<?php
declare(strict_types=1);

final class ReportJob
{
    public static function path(int $id): string
    {
        return dirname(__DIR__, 2) . "/storage/reports/report-$id.csv";
    }

    public static function handle(int $id): void
    {
        $pdo = db();
        $stmt = $pdo->prepare("UPDATE report_requests SET status='processing', error=NULL
                               WHERE id=:id AND status IN ('queued','processing')");
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            return;
        }
        $path = self::path($id);
        $temporary = tempnam(dirname($path), 'tmp-');
        if ($temporary === false) {
            throw new RuntimeException('Недоступен каталог отчётов');
        }
        try {
            $file = fopen($temporary, 'wb');
            if ($file === false) {
                throw new RuntimeException('Не удалось открыть CSV');
            }
            try {
                if (fwrite($file, "\xEF\xBB\xBF") !== 3) {
                    throw new RuntimeException('Не удалось записать BOM');
                }
                self::write($file, ['ID', 'Мероприятие', 'Статус', 'Начало', 'Площадка']);
                $pdo->beginTransaction();
                $pdo->exec('DECLARE export_events NO SCROLL CURSOR FOR
                    SELECT e.id, e.title, e.status, e.starts_at, v.name
                    FROM events e JOIN venues v ON v.id=e.venue_id ORDER BY e.id');
                $count = 0;
                while ($rows = $pdo->query('FETCH FORWARD 1000 FROM export_events')->fetchAll(PDO::FETCH_NUM)) {
                    foreach ($rows as $row) {
                        self::write($file, $row);
                        $count++;
                    }
                }
                $pdo->commit();
            } finally {
                fclose($file);
            }
            if (!rename($temporary, $path)) {
                throw new RuntimeException('Не удалось опубликовать CSV');
            }
            $pdo->prepare("UPDATE report_requests SET status='done', rows_count=:count WHERE id=:id")
                ->execute(['count' => $count, 'id' => $id]);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    private static function write($file, array $fields): void
    {
        foreach ($fields as &$field) {
            if (is_string($field) && preg_match('/^[\s]*[=+@-]|^[\t\r\n]/u', $field)) {
                $field = "'" . $field;
            }
        }
        if (fputcsv($file, $fields, ';', '"', '', "\r\n") === false) {
            throw new RuntimeException('Не удалось записать строку CSV');
        }
    }

    public static function fail(int $id): void
    {
        db()->prepare("UPDATE report_requests SET status='failed', error='Не удалось сформировать CSV'
                       WHERE id=:id AND status IN ('queued','processing')")->execute(['id' => $id]);
    }
}
