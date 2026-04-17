<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../model/Role.php';
require_once __DIR__ . '/../model/Permission.php';
require_once __DIR__ . '/../repository/RoleRepository.php';
require_once __DIR__ . '/../repository/PermissionRepository.php';

use ApiException;
use Role;

class RoleService
{
    private $roleRepository;
    private $permissionRepository;
    private $protectedRoleCodes = ['SUPER_ADMIN', 'ADMIN', 'USER'];

    public function __construct()
    {
        $this->roleRepository = new RoleRepository();
        $this->permissionRepository = new PermissionRepository();
    }

    public function listRoles(array $filters = [])
    {
        if ($this->shouldUsePaginatedList($filters)) {
            $result = $this->roleRepository->findPaginated($filters);

            return [
                'items' => array_map(function (Role $role) {
                    return $this->buildRolePayload($role);
                }, $result['items']),
                'pagination' => $result['pagination'],
                'filters' => [
                    'search' => trim((string) ($filters['search'] ?? '')),
                ],
            ];
        }

        $roles = $this->roleRepository->findAll();

        return array_map(function (Role $role) {
            return $this->buildRolePayload($role);
        }, $roles);
    }

    public function getRoleById($roleId)
    {
        $role = $this->requireRole($roleId);

        return $this->buildRolePayload($role);
    }

    public function createRole(array $payload)
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $code = strtoupper(trim((string) ($payload['code'] ?? '')));

        if ($name === '' || $code === '') {
            throw new ApiException('Les champs name et code sont obligatoires.', 422, 'validation_error');
        }

        if ($this->roleRepository->codeExists($code)) {
            throw new ApiException('Ce code role existe deja.', 409, 'role_code_exists');
        }

        $role = $this->roleRepository->create([
            'name' => $name,
            'code' => $code,
            'description' => $this->nullableTrim($payload, 'description'),
        ]);

        if (array_key_exists('permission_codes', $payload)) {
            $this->roleRepository->syncPermissions($role->getId(), $this->resolvePermissionIds($payload['permission_codes']));
        }

        return $this->buildRolePayload($this->requireRole($role->getId()));
    }

    public function updateRole($roleId, array $payload)
    {
        $existingRole = $this->requireRole($roleId);
        $name = trim((string) ($payload['name'] ?? $existingRole->toSafeArray()['name']));
        $code = strtoupper(trim((string) ($payload['code'] ?? $existingRole->getCode())));

        if ($name === '' || $code === '') {
            throw new ApiException('Les champs name et code sont obligatoires.', 422, 'validation_error');
        }

        if ($this->roleRepository->codeExists($code, $existingRole->getId())) {
            throw new ApiException('Ce code role existe deja.', 409, 'role_code_exists');
        }

        if (in_array($existingRole->getCode(), $this->protectedRoleCodes, true) && $existingRole->getCode() !== $code) {
            throw new ApiException('Le code d\'un role systeme ne peut pas etre modifie.', 422, 'protected_role');
        }

        $role = $this->roleRepository->update($existingRole->getId(), [
            'name' => $name,
            'code' => $code,
            'description' => $this->nullableTrim($payload, 'description', $existingRole->toSafeArray()['description']),
        ]);

        if (array_key_exists('permission_codes', $payload)) {
            $this->roleRepository->syncPermissions($role->getId(), $this->resolvePermissionIds($payload['permission_codes']));
        }

        return $this->buildRolePayload($this->requireRole($role->getId()));
    }

    public function deleteRole($roleId)
    {
        $role = $this->requireRole($roleId);
        if (in_array($role->getCode(), $this->protectedRoleCodes, true)) {
            throw new ApiException('Ce role systeme ne peut pas etre supprime.', 422, 'protected_role');
        }

        $deleted = $this->roleRepository->delete($role->getId());
        if (!$deleted) {
            throw new ApiException('Suppression du role impossible.', 500, 'delete_failed');
        }

        return [
            'message' => 'Role supprime avec succes.',
        ];
    }

    public function syncRolePermissions($roleId, array $payload)
    {
        $role = $this->requireRole($roleId);
        $this->roleRepository->syncPermissions($role->getId(), $this->resolvePermissionIds($payload['permission_codes'] ?? []));

        return $this->buildRolePayload($this->requireRole($role->getId()));
    }

    private function buildRolePayload(Role $role)
    {
        $payload = $role->toSafeArray();
        $payload['permissions'] = $this->roleRepository->getPermissions($role->getId());

        return $payload;
    }

    private function requireRole($roleId)
    {
        $role = $this->roleRepository->findById((int) $roleId);
        if (!$role) {
            throw new ApiException('Role introuvable.', 404, 'role_not_found');
        }

        return $role;
    }

    private function resolvePermissionIds($permissionCodes)
    {
        if (!is_array($permissionCodes)) {
            throw new ApiException('permission_codes doit etre un tableau.', 422, 'validation_error');
        }

        if (empty($permissionCodes)) {
            return [];
        }

        $rows = $this->permissionRepository->findIdsByCodes($permissionCodes);
        $foundCodes = array_map(function ($row) {
            return $row['code'];
        }, $rows);

        foreach ($permissionCodes as $permissionCode) {
            if (!in_array($permissionCode, $foundCodes, true)) {
                throw new ApiException('Permission introuvable: ' . $permissionCode, 404, 'permission_not_found');
            }
        }

        return array_values(array_map(function ($row) {
            return (int) $row['id'];
        }, $rows));
    }

    private function nullableTrim(array $payload, $key, $default = null)
    {
        if (!array_key_exists($key, $payload)) {
            return $default;
        }

        $value = trim((string) $payload[$key]);

        return $value === '' ? null : $value;
    }

    private function shouldUsePaginatedList(array $filters)
    {
        foreach (['search', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $filters) && $filters[$key] !== null && $filters[$key] !== '') {
                return true;
            }
        }

        return false;
    }
}
