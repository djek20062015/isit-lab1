<?php
declare(strict_types=1);

final class NotificationJob
{
    public static function handle(array $job): void
    {
        Jobs::pause(max(0, (float) (getenv('NOTIFICATION_DELAY') ?: 0)));
        $data = $job['payload'];
        $message = sprintf('Мероприятие «%s» (№ %d): %s → %s',
            $data['title'], $data['event_id'], $data['old_status'], $data['new_status']);
        $sql = "INSERT INTO notifications(job_id, user_id, message)
                SELECT :job, id, :message FROM users WHERE role='manager' AND id<>:actor
                ON CONFLICT (job_id, user_id) DO NOTHING";
        db()->prepare($sql)->execute(['job' => $job['id'], 'message' => $message, 'actor' => $data['actor_id']]);
    }
}
