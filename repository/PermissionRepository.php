<?php

require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Permission.php';

class PermissionRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
    }

    public function findAll()
    {
        $statement = $this->connection->query('SELECT * FROM permissions ORDER BY module ASC, name ASC');
        $rows = $statement->fetchAll();

        return array_map(function ($row) {
            return \Permission::fromArray($row);
        }, $rows);
    }

    public function findIdsByCodes(array $codes)
    {
        if (empty($codes)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $statement = $this->connection->prepare('SELECT id, code FROM permissions WHERE code IN (' . $placeholders . ')');
        $statement->execute(array_values($codes));

        return $statement->fetchAll();
    }
}
