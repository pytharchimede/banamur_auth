<?php

require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Permission.php';

use Permission;

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
            return Permission::fromArray($row);
        }, $rows);
    }

    public function findPaginated(array $filters)
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $query = $this->buildFilteredQuery($filters);

        $countStatement = $this->connection->prepare('SELECT COUNT(*) FROM permissions p' . $query['where']);
        $countStatement->execute($query['params']);
        $total = (int) $countStatement->fetchColumn();

        $statement = $this->connection->prepare(
            'SELECT p.* FROM permissions p' . $query['where'] . ' ORDER BY p.module ASC, p.name ASC, p.id ASC LIMIT :limit OFFSET :offset'
        );

        foreach ($query['params'] as $key => $value) {
            $statement->bindValue($key, $value);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => array_map(function ($row) {
                return Permission::fromArray($row);
            }, $statement->fetchAll()),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ];
    }

    public function findById($permissionId)
    {
        $statement = $this->connection->prepare('SELECT * FROM permissions WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $permissionId]);
        $row = $statement->fetch();

        return $row ? Permission::fromArray($row) : null;
    }

    public function update($permissionId, array $data)
    {
        $statement = $this->connection->prepare(
            'UPDATE permissions
             SET name = :name,
                 description = :description,
                 module = :module
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $permissionId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'module' => $data['module'] ?? null,
        ]);

        return $this->findById($permissionId);
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

    public function getRoles($permissionId)
    {
        $statement = $this->connection->prepare(
            'SELECT r.id, r.name, r.code, r.description
             FROM roles r
             INNER JOIN role_permissions rp ON rp.role_id = r.id
             WHERE rp.permission_id = :permission_id
             ORDER BY r.name ASC'
        );
        $statement->execute(['permission_id' => $permissionId]);

        return $statement->fetchAll();
    }

    public function syncRoles($permissionId, array $roleIds)
    {
        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare('DELETE FROM role_permissions WHERE permission_id = :permission_id');
            $delete->execute(['permission_id' => $permissionId]);

            if (!empty($roleIds)) {
                $insert = $this->connection->prepare(
                    'INSERT INTO role_permissions (role_id, permission_id, created_at)
                     VALUES (:role_id, :permission_id, NOW())'
                );

                foreach ($roleIds as $roleId) {
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

    private function buildFilteredQuery(array $filters)
    {
        $conditions = [];
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));
        $module = trim((string) ($filters['module'] ?? ''));
        $roleCode = trim((string) ($filters['role_code'] ?? ''));

        if ($search !== '') {
            $conditions[] = '(p.name LIKE :search OR p.code LIKE :search OR COALESCE(p.description, \'\') LIKE :search OR COALESCE(p.module, \'\') LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($module !== '') {
            $conditions[] = 'p.module = :module';
            $params[':module'] = $module;
        }

        if ($roleCode !== '') {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM role_permissions rp_filter
                INNER JOIN roles r_filter ON r_filter.id = rp_filter.role_id
                WHERE rp_filter.permission_id = p.id AND r_filter.code = :role_code
            )';
            $params[':role_code'] = $roleCode;
        }

        return [
            'where' => empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions),
            'params' => $params,
        ];
    }
}
