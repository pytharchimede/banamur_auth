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

    private function getDatabaseName()
    {
        \EnvConfig::loadFromDirectory(__DIR__);

        return \EnvConfig::get('PROJECT_DB_NAME', 'banamur_auth');
    }
}
