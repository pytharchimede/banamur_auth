<?php

require_once __DIR__ . '/../model/Database.php';

class AuthLogRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
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

    public function findRecent($limit = 100)
    {
        $limit = max(1, min(500, (int) $limit));

        $statement = $this->connection->prepare(
            'SELECT
                al.id,
                al.user_id,
                al.event_type,
                al.message,
                al.ip_address,
                al.user_agent,
                al.created_at,
                u.username,
                u.email,
                CONCAT(COALESCE(u.first_name, \'\'), CASE WHEN u.first_name IS NOT NULL AND u.last_name IS NOT NULL THEN \' \' ELSE \'\' END, COALESCE(u.last_name, \'\')) AS full_name
             FROM auth_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT ' . $limit
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findPaginated(array $filters)
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $query = $this->buildFilteredQuery($filters);

        $countStatement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM auth_logs al
             LEFT JOIN users u ON u.id = al.user_id' . $query['where']
        );
        $countStatement->execute($query['params']);
        $total = (int) $countStatement->fetchColumn();

        $statement = $this->connection->prepare(
            'SELECT
                al.id,
                al.user_id,
                al.event_type,
                al.message,
                al.ip_address,
                al.user_agent,
                al.created_at,
                u.username,
                u.email,
                CONCAT(COALESCE(u.first_name, \'\'), CASE WHEN u.first_name IS NOT NULL AND u.last_name IS NOT NULL THEN \' \' ELSE \'\' END, COALESCE(u.last_name, \'\')) AS full_name
             FROM auth_logs al
             LEFT JOIN users u ON u.id = al.user_id' . $query['where'] . '
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($query['params'] as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];
    }

    public function countRecentByIpAndEvents($ipAddress, array $eventTypes, $windowInMinutes = 15)
    {
        $ipAddress = trim((string) $ipAddress);
        if ($ipAddress === '' || empty($eventTypes)) {
            return 0;
        }

        $windowInMinutes = max(1, (int) $windowInMinutes);

        $placeholders = [];
        $params = [
            ':ip_address' => $ipAddress,
        ];

        foreach (array_values($eventTypes) as $index => $eventType) {
            $placeholder = ':event_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $eventType;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM auth_logs
             WHERE ip_address = :ip_address
               AND event_type IN (' . implode(', ', $placeholders) . ')
             AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $windowInMinutes . ' MINUTE)'
        );
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    private function buildFilteredQuery(array $filters)
    {
        $conditions = [];
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));
        $eventType = trim((string) ($filters['event_type'] ?? ''));

        if ($search !== '') {
            $conditions[] = '(al.event_type LIKE :search OR al.message LIKE :search OR COALESCE(u.username, \'\') LIKE :search OR COALESCE(u.email, \'\') LIKE :search OR COALESCE(CONCAT(COALESCE(u.first_name, \'\'), \' \', COALESCE(u.last_name, \'\')), \'\') LIKE :search OR COALESCE(al.ip_address, \'\') LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($eventType !== '') {
            $conditions[] = 'al.event_type = :event_type';
            $params[':event_type'] = $eventType;
        }

        return [
            'where' => empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions),
            'params' => $params,
        ];
    }
}
