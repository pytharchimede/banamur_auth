<?php

require_once __DIR__ . '/../model/Database.php';

class AuthLogRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function log($userId, $eventType, $message, $ipAddress, $userAgent)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO auth_logs (user_id, event_type, message, ip_address, user_agent, created_at)
             VALUES (:user_id, :event_type, :message, :ip_address, :user_agent, NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'event_type' => $eventType,
            'message' => $message,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
