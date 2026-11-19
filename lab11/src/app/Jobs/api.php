<?php
declare(strict_types=1);

function job_routes(string $path, string $method, array $user): void
{
    if ($path === '/api/notifications' && $method === 'GET') {
        $stmt = db()->prepare('SELECT job_id, message, created_at FROM notifications
                               WHERE user_id=:id ORDER BY created_at DESC, job_id LIMIT 100');
        $stmt->execute(['id' => $user['id']]);
        respond($stmt->fetchAll());
    }
    if ($path === '/api/reports' && $method === 'POST') {
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO report_requests(user_id) VALUES (:user) RETURNING id');
        $stmt->execute(['user' => $user['id']]);
        $id = (int) $stmt->fetchColumn();
        $job = Jobs::make('report', ['request_id' => $id]);
        if (!Jobs::sync()) {
            Jobs::stage($job);
        }
        $pdo->commit();
        Jobs::afterCommit($job);
        $location = "/api/reports/$id";
        respond(['id' => $id, 'status' => Jobs::sync() ? 'done' : 'queued'],
            Jobs::sync() ? 201 : 202, ['Location' => $location, 'X-Job-Id' => $job['id']]);
    }
    if (preg_match('#^/api/reports/(\d+)(/download)?$#', $path, $match) && $method === 'GET') {
        $id = (int) $match[1];
        $stmt = db()->prepare('SELECT id, user_id, status, rows_count, error FROM report_requests WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $report = $stmt->fetch();
        if (!$report) {
            fail('Заявка не найдена', 404);
        }
        if ($user['role'] !== 'manager' && (int) $report['user_id'] !== (int) $user['id']) {
            fail('Недостаточно прав', 403);
        }
        if (isset($match[2])) {
            $file = ReportJob::path($id);
            if ($report['status'] !== 'done' || !is_file($file)) {
                fail('Отчёт недоступен', 409);
            }
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=report-$id.csv");
            readfile($file);
            exit;
        }
        unset($report['user_id']);
        $report['download_url'] = $report['status'] === 'done' ? "/api/reports/$id/download" : null;
        respond($report);
    }
}
