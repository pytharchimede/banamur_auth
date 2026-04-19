<?php

require_once __DIR__ . '/../model/Database.php';

class AuthSessionRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
    }

    public function create($userId, $tokenHash, $ipAddress, $userAgent, $expiresAt)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO auth_sessions (user_id, token_hash, ip_address, user_agent, expires_at, created_at)
             VALUES (:user_id, :token_hash, :ip_address, :user_agent, :expires_at, NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findActiveById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM auth_sessions
             WHERE id = :id
               AND revoked_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $id]);

        return $statement->fetch() ?: null;
    }

    public function findActiveByTokenHash($tokenHash)
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM auth_sessions
             WHERE token_hash = :token_hash
               AND revoked_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);

        return $statement->fetch() ?: null;
    }

    public function findActiveByToken($plainToken)
    {
        return $this->findActiveByTokenHash(hash('sha256', $plainToken));
    }

    public function revokeByTokenHash($tokenHash)
    {
        $statement = $this->connection->prepare(
            'UPDATE auth_sessions SET revoked_at = NOW() WHERE token_hash = :token_hash AND revoked_at IS NULL'
        );
        $statement->execute(['token_hash' => $tokenHash]);

        return $statement->rowCount() > 0;
    }

    public function revokeById($id)
    {
        $statement = $this->connection->prepare(
            'UPDATE auth_sessions SET revoked_at = NOW() WHERE id = :id AND revoked_at IS NULL'
        );
        $statement->execute(['id' => (int) $id]);

        return $statement->rowCount() > 0;
    }
}
