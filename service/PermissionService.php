<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../model/Permission.php';
require_once __DIR__ . '/../model/Role.php';
require_once __DIR__ . '/../repository/PermissionRepository.php';
require_once __DIR__ . '/../repository/RoleRepository.php';
require_once __DIR__ . '/../repository/AuthLogRepository.php';

use ApiException;
use Permission;
use Role;

class PermissionService
{
    private $permissionRepository;
    private $roleRepository;
    private $authLogRepository;

    public function __construct()
    {
        $this->permissionRepository = new PermissionRepository();
        $this->roleRepository = new RoleRepository();
        $this->authLogRepository = new AuthLogRepository();
    }

    public function listPermissions(array $filters = [])
    {
        if ($this->shouldUsePaginatedList($filters)) {
            $result = $this->permissionRepository->findPaginated($filters);

            return [
                'items' => array_map(function (Permission $permission) {
                    return $this->buildPermissionPayload($permission);
                }, $result['items']),
                'pagination' => $result['pagination'],
                'filters' => [
                    'search' => trim((string) ($filters['search'] ?? '')),
                    'module' => trim((string) ($filters['module'] ?? '')),
                    'role_code' => trim((string) ($filters['role_code'] ?? '')),
                ],
            ];
        }

        $permissions = $this->permissionRepository->findAll();

        return array_map(function (Permission $permission) {
            return $this->buildPermissionPayload($permission);
        }, $permissions);
    }

    public function getPermissionById($permissionId)
    {
        return $this->buildPermissionPayload($this->requirePermission($permissionId));
    }

    public function updatePermission($permissionId, array $payload, array $context = [])
    {
        $permission = $this->requirePermission($permissionId);
        $safe = $permission->toSafeArray();
        $name = trim((string) ($payload['name'] ?? $safe['name']));

        if ($name === '') {
            throw new ApiException('Le champ name est obligatoire.', 422, 'validation_error');
        }

        $updatedPermission = $this->permissionRepository->update($permission->getId(), [
            'name' => $name,
            'description' => $this->nullableTrim($payload, 'description', $safe['description']),
            'module' => $this->nullableTrim($payload, 'module', $safe['module']),
        ]);

        if (array_key_exists('role_codes', $payload)) {
            $this->permissionRepository->syncRoles($updatedPermission->getId(), $this->resolveRoleIds($payload['role_codes']));
        }

        $this->authLogRepository->log(
            $context['authenticated_user_id'] ?? null,
            'permission_updated',
            'Permission mise a jour : ' . $updatedPermission->getCode(),
            $context['ip_address'] ?? null,
            $context['user_agent'] ?? null
        );

        return $this->buildPermissionPayload($this->requirePermission($updatedPermission->getId()));
    }

    private function buildPermissionPayload(Permission $permission)
    {
        $payload = $permission->toSafeArray();
        $payload['roles'] = $this->permissionRepository->getRoles($permission->getId());

        return $payload;
    }

    private function requirePermission($permissionId)
    {
        $permission = $this->permissionRepository->findById((int) $permissionId);
        if (!$permission) {
            throw new ApiException('Permission introuvable.', 404, 'permission_not_found');
        }

        return $permission;
    }

    private function resolveRoleIds($roleCodes)
    {
        if (!is_array($roleCodes)) {
            throw new ApiException('role_codes doit etre un tableau.', 422, 'validation_error');
        }

        if (empty($roleCodes)) {
            return [];
        }

        $roles = $this->roleRepository->findByCodes($roleCodes);
        $foundCodes = array_map(function (Role $role) {
            return $role->getCode();
        }, $roles);

        foreach ($roleCodes as $roleCode) {
            if (!in_array((string) $roleCode, $foundCodes, true)) {
                throw new ApiException('Role introuvable: ' . $roleCode, 404, 'role_not_found');
            }
        }

        return array_values(array_map(function (Role $role) {
            return (int) $role->getId();
        }, $roles));
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
        foreach (['search', 'module', 'role_code', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $filters) && $filters[$key] !== null && $filters[$key] !== '') {
                return true;
            }
        }

        return false;
    }
}
