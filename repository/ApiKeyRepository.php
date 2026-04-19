<?php

require_once __DIR__ . '/../model/Database.php';

class ApiKeyRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
    }

    public function create($userId, $name, $keyPrefix, $keyHash, $expiresAt)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO api_keys (user_id, name, key_prefix, key_hash, expires_at, created_at)
             VALUES (:user_id, :name, :key_prefix, :key_hash, :expires_at, NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'name' => $name,
            'key_prefix' => $keyPrefix,
            'key_hash' => $keyHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findAll(array $filters = [])
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = 'ak.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (empty($filters['include_revoked'])) {
            $conditions[] = 'ak.revoked_at IS NULL';
        }

        if (empty($filters['include_expired'])) {
            $conditions[] = '(ak.expires_at IS NULL OR ak.expires_at > NOW())';
        }

        $statement = $this->connection->prepare(
            'SELECT
                ak.id,
                ak.user_id,
                ak.name,
                ak.key_prefix,
                ak.last_used_at,
                ak.expires_at,
                ak.revoked_at,
                ak.created_at,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.status
             FROM api_keys ak
             INNER JOIN users u ON u.id = ak.user_id' .
                (empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions)) . '
             ORDER BY ak.created_at DESC, ak.id DESC'
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT
                ak.id,
                ak.user_id,
                ak.name,
                ak.key_prefix,
                ak.last_used_at,
                ak.expires_at,
                ak.revoked_at,
                ak.created_at,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.status
             FROM api_keys ak
             INNER JOIN users u ON u.id = ak.user_id
             WHERE ak.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $id]);

        return $statement->fetch() ?: null;
    }

    public function findActiveByPlainKey($plainKey)
    {
        $statement = $this->connection->prepare(
            'SELECT *
             FROM api_keys
             WHERE key_hash = :key_hash
               AND revoked_at IS NULL
               AND (expires_at IS NULL OR expires_at > NOW())
             LIMIT 1'
        );
        $statement->execute([
            'key_hash' => hash('sha256', $plainKey),
        ]);

        return $statement->fetch() ?: null;
    }

    public function revokeById($id)
    {
        $statement = $this->connection->prepare(
            'UPDATE api_keys
             SET revoked_at = NOW()
             WHERE id = :id
               AND revoked_at IS NULL'
        );
        $statement->execute(['id' => (int) $id]);

        return $statement->rowCount() > 0;
    }

    public function touchLastUsedAt($id)
    {
        $statement = $this->connection->prepare(
            'UPDATE api_keys SET last_used_at = NOW() WHERE id = :id'
        );
        $statement->execute(['id' => (int) $id]);
    }
}
