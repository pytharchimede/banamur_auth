<?php

require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/User.php';

class UserRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
    }

    public function findByEmailOrUsername($identifier)
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM users WHERE email = :identifier OR username = :identifier LIMIT 1'
        );
        $statement->execute(['identifier' => $identifier]);
        $row = $statement->fetch();

        return $row ? \User::fromArray($row) : null;
    }

    public function findById($userId)
    {
        $statement = $this->connection->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $row = $statement->fetch();

        return $row ? \User::fromArray($row) : null;
    }

    public function findAll()
    {
        $statement = $this->connection->query('SELECT * FROM users ORDER BY created_at DESC, id DESC');
        $rows = $statement->fetchAll();

        return array_map(function ($row) {
            return \User::fromArray($row);
        }, $rows);
    }

    public function usernameExists($username, $excludeId = null)
    {
        $sql = 'SELECT id FROM users WHERE username = :username';
        $params = ['username' => $username];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function emailExists($email, $excludeId = null)
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function create(array $data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (
                username,
                email,
                password_hash,
                first_name,
                last_name,
                phone,
                status,
                created_at,
                updated_at
            ) VALUES (
                :username,
                :email,
                :password_hash,
                :first_name,
                :last_name,
                :phone,
                :status,
                NOW(),
                NOW()
            )'
        );

        $statement->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return $this->findById((int) $this->connection->lastInsertId());
    }

    public function updateLastLoginAt($userId)
    {
        $statement = $this->connection->prepare(
            'UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $statement->execute(['id' => $userId]);
    }

    public function update($userId, array $data)
    {
        $fields = [
            'username = :username',
            'email = :email',
            'first_name = :first_name',
            'last_name = :last_name',
            'phone = :phone',
            'status = :status',
            'updated_at = NOW()',
        ];
        $params = [
            'id' => $userId,
            'username' => $data['username'],
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];

        if (array_key_exists('password_hash', $data)) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $statement->execute($params);

        return $this->findById($userId);
    }

    public function delete($userId)
    {
        $statement = $this->connection->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $userId]);

        return $statement->rowCount() > 0;
    }

    public function assignRoleByCode($userId, $roleCode)
    {
        $statement = $this->connection->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $roleCode]);
        $roleId = $statement->fetchColumn();

        if (!$roleId) {
            return;
        }

        $insert = $this->connection->prepare(
            'INSERT IGNORE INTO user_roles (user_id, role_id, assigned_by, assigned_at) VALUES (:user_id, :role_id, NULL, NOW())'
        );
        $insert->execute([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }

    public function getRoles($userId)
    {
        $statement = $this->connection->prepare(
            'SELECT r.id, r.name, r.code, r.description
             FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id
             ORDER BY r.name ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function getRoleCodes($userId)
    {
        return array_values(array_map(function ($role) {
            return $role['code'];
        }, $this->getRoles($userId)));
    }

    public function getPermissions($userId)
    {
        $statement = $this->connection->prepare(
            'SELECT DISTINCT p.id, p.name, p.code, p.description, p.module, p.created_at
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :user_id
             ORDER BY p.module ASC, p.name ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function getPermissionCodes($userId)
    {
        return array_values(array_map(function ($permission) {
            return $permission['code'];
        }, $this->getPermissions($userId)));
    }

    public function syncRoles($userId, array $roleIds)
    {
        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
            $delete->execute(['user_id' => $userId]);

            if (!empty($roleIds)) {
                $insert = $this->connection->prepare(
                    'INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at)
                     VALUES (:user_id, :role_id, NULL, NOW())'
                );

                foreach ($roleIds as $roleId) {
                    $insert->execute([
                        'user_id' => $userId,
                        'role_id' => $roleId,
                    ]);
                }
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }
}
