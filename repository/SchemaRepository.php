<?php

require_once __DIR__ . '/../model/Database.php';

class SchemaRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
    }

    public function databaseExists()
    {
        return \Database::databaseExists($this->getDatabaseName());
    }

    public function tableExists($tableName)
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :database_name AND TABLE_NAME = :table_name'
        );
        $statement->execute([
            'database_name' => $this->getDatabaseName(),
            'table_name' => $tableName,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function createTable($sql)
    {
        $this->connection->exec($sql);
    }

    public function insertRoleIfMissing($name, $code, $description)
    {
        $statement = $this->connection->prepare('SELECT id FROM roles WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);
        if ($statement->fetchColumn()) {
            return;
        }

        $insert = $this->connection->prepare(
            'INSERT INTO roles (name, code, description, created_at, updated_at) VALUES (:name, :code, :description, NOW(), NOW())'
        );
        $insert->execute([
            'name' => $name,
            'code' => $code,
            'description' => $description,
        ]);
    }

    public function insertPermissionIfMissing($name, $code, $description, $module)
    {
        $statement = $this->connection->prepare('SELECT id FROM permissions WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);
        if ($statement->fetchColumn()) {
            return;
        }

        $insert = $this->connection->prepare(
            'INSERT INTO permissions (name, code, description, module, created_at)
             VALUES (:name, :code, :description, :module, NOW())'
        );
        $insert->execute([
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'module' => $module,
        ]);
    }

    public function assignPermissionToRoleIfMissing($roleCode, $permissionCode)
    {
        $statement = $this->connection->prepare(
            'SELECT rp.id
             FROM role_permissions rp
             INNER JOIN roles r ON r.id = rp.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE r.code = :role_code AND p.code = :permission_code
             LIMIT 1'
        );
        $statement->execute([
            'role_code' => $roleCode,
            'permission_code' => $permissionCode,
        ]);

        if ($statement->fetchColumn()) {
            return;
        }

        $insert = $this->connection->prepare(
            'INSERT INTO role_permissions (role_id, permission_id, created_at)
             SELECT r.id, p.id, NOW()
             FROM roles r, permissions p
             WHERE r.code = :role_code AND p.code = :permission_code'
        );
        $insert->execute([
            'role_code' => $roleCode,
            'permission_code' => $permissionCode,
        ]);
    }

    public function hasUserWithRoleCodes(array $roleCodes)
    {
        if (empty($roleCodes)) {
            return false;
        }

        $placeholders = implode(', ', array_fill(0, count($roleCodes), '?'));
        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.code IN (' . $placeholders . ')'
        );
        $statement->execute(array_values($roleCodes));

        return (int) $statement->fetchColumn() > 0;
    }

    public function findUserIdByUsername($username)
    {
        $statement = $this->connection->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);

        $userId = $statement->fetchColumn();

        return $userId ? (int) $userId : null;
    }

    public function findUserIdByEmail($email)
    {
        $statement = $this->connection->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $userId = $statement->fetchColumn();

        return $userId ? (int) $userId : null;
    }

    public function createUser(array $data)
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

        return (int) $this->connection->lastInsertId();
    }

    public function updateBootstrapAdmin($userId, array $data)
    {
        $statement = $this->connection->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 first_name = :first_name,
                 last_name = :last_name,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => (int) $userId,
            'password_hash' => $data['password_hash'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function assignRoleToUserIfMissing($userId, $roleCode)
    {
        $statement = $this->connection->prepare(
            'SELECT ur.id
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id AND r.code = :role_code
             LIMIT 1'
        );
        $statement->execute([
            'user_id' => (int) $userId,
            'role_code' => $roleCode,
        ]);

        if ($statement->fetchColumn()) {
            return;
        }

        $insert = $this->connection->prepare(
            'INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at)
             SELECT :user_id, r.id, NULL, NOW()
             FROM roles r
             WHERE r.code = :role_code'
        );
        $insert->execute([
            'user_id' => (int) $userId,
            'role_code' => $roleCode,
        ]);
    }

    public function assignRoleToUsersWithoutAnyRole($roleCode)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at)
             SELECT u.id, r.id, NULL, NOW()
             FROM users u
             INNER JOIN roles r ON r.code = :role_code
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             WHERE ur.id IS NULL'
        );
        $statement->execute([
            'role_code' => $roleCode,
        ]);

        return $statement->rowCount();
    }

    private function getDatabaseName()
    {
        \EnvConfig::loadFromDirectory(__DIR__);

        return \EnvConfig::get('PROJECT_DB_NAME', 'banamur_auth');
    }
}
