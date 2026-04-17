<?php

require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Role.php';

use Role;

class RoleRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = \Database::getConnection();
    }

    public function findAll()
    {
        $statement = $this->connection->query('SELECT * FROM roles ORDER BY name ASC');
        $rows = $statement->fetchAll();

        return array_map(function ($row) {
            return Role::fromArray($row);
        }, $rows);
    }

    public function findPaginated(array $filters)
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $query = $this->buildFilteredQuery($filters);

        $countStatement = $this->connection->prepare('SELECT COUNT(*) FROM roles r' . $query['where']);
        $countStatement->execute($query['params']);
        $total = (int) $countStatement->fetchColumn();

        $statement = $this->connection->prepare(
            'SELECT r.* FROM roles r' . $query['where'] . ' ORDER BY r.name ASC, r.id ASC LIMIT :limit OFFSET :offset'
        );

        foreach ($query['params'] as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => array_map(function ($row) {
                return Role::fromArray($row);
            }, $statement->fetchAll()),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];
    }

    public function findById($roleId)
    {
        $statement = $this->connection->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $roleId]);
        $row = $statement->fetch();

        return $row ? Role::fromArray($row) : null;
    }

    public function findByCode($code)
    {
        $statement = $this->connection->prepare('SELECT * FROM roles WHERE code = :code LIMIT 1');
        $statement->execute(['code' => $code]);
        $row = $statement->fetch();

        return $row ? Role::fromArray($row) : null;
    }

    public function codeExists($code, $excludeId = null)
    {
        $sql = 'SELECT id FROM roles WHERE code = :code';
        $params = ['code' => $code];

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
            'INSERT INTO roles (name, code, description, created_at, updated_at)
             VALUES (:name, :code, :description, NOW(), NOW())'
        );
        $statement->execute([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        return $this->findById((int) $this->connection->lastInsertId());
    }

    public function update($roleId, array $data)
    {
        $statement = $this->connection->prepare(
            'UPDATE roles
             SET name = :name,
                 code = :code,
                 description = :description,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $roleId,
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
        ]);

        return $this->findById($roleId);
    }

    public function delete($roleId)
    {
        $statement = $this->connection->prepare('DELETE FROM roles WHERE id = :id');
        $statement->execute(['id' => $roleId]);

        return $statement->rowCount() > 0;
    }

    public function getPermissions($roleId)
    {
        $statement = $this->connection->prepare(
            'SELECT p.id, p.name, p.code, p.description, p.module, p.created_at
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.module ASC, p.name ASC'
        );
        $statement->execute(['role_id' => $roleId]);

        return $statement->fetchAll();
    }

    public function syncPermissions($roleId, array $permissionIds)
    {
        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
            $delete->execute(['role_id' => $roleId]);

            if (!empty($permissionIds)) {
                $insert = $this->connection->prepare(
                    'INSERT INTO role_permissions (role_id, permission_id, created_at)
                     VALUES (:role_id, :permission_id, NOW())'
                );

                foreach ($permissionIds as $permissionId) {
                    $insert->execute([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function findByCodes(array $codes)
    {
        if (empty($codes)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $statement = $this->connection->prepare('SELECT * FROM roles WHERE code IN (' . $placeholders . ') ORDER BY name ASC');
        $statement->execute(array_values($codes));

        return array_map(function ($row) {
            return Role::fromArray($row);
        }, $statement->fetchAll());
    }

    private function buildFilteredQuery(array $filters)
    {
        $conditions = [];
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $conditions[] = '(r.name LIKE :search OR r.code LIKE :search OR COALESCE(r.description, \'\') LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        return [
            'where' => empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions),
            'params' => $params,
        ];
    }
}
