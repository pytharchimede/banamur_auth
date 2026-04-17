<?php

require_once __DIR__ . '/../model/ApiException.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/RoleRepository.php';
require_once __DIR__ . '/../repository/AuthLogRepository.php';

use ApiException;
use User;
use UserRepository;
use RoleRepository;
use AuthLogRepository;

class UserService
{
    private $userRepository;
    private $roleRepository;
    private $authLogRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->roleRepository = new RoleRepository();
        $this->authLogRepository = new AuthLogRepository();
    }

    public function listUsers(array $filters = [])
    {
        if ($this->shouldUsePaginatedList($filters)) {
            $result = $this->userRepository->findPaginated($filters);

            return [
                'items' => array_map(function (User $user) {
                    return $this->buildUserPayload($user);
                }, $result['items']),
                'pagination' => $result['pagination'],
                'filters' => [
                    'search' => trim((string) ($filters['search'] ?? '')),
                    'status' => trim((string) ($filters['status'] ?? '')),
                    'role_code' => trim((string) ($filters['role_code'] ?? '')),
                ],
            ];
        }

        $users = $this->userRepository->findAll();

        return array_map(function (User $user) {
            return $this->buildUserPayload($user);
        }, $users);
    }

    public function getUserById($userId)
    {
        $user = $this->requireUser($userId);

        return $this->buildUserPayload($user);
    }

    public function createUser(array $payload, array $context)
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            throw new ApiException('Les champs username, email et password sont obligatoires.', 422, 'validation_error');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Adresse email invalide.', 422, 'validation_error');
        }

        if (strlen($password) < 8) {
            throw new ApiException('Le mot de passe doit contenir au moins 8 caracteres.', 422, 'validation_error');
        }

        if ($this->userRepository->usernameExists($username)) {
            throw new ApiException('Ce nom d\'utilisateur existe deja.', 409, 'username_exists');
        }

        if ($this->userRepository->emailExists($email)) {
            throw new ApiException('Cet email existe deja.', 409, 'email_exists');
        }

        $user = $this->userRepository->create([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $this->nullableTrim($payload, 'first_name'),
            'last_name' => $this->nullableTrim($payload, 'last_name'),
            'phone' => $this->nullableTrim($payload, 'phone'),
            'status' => $this->normalizeStatus($payload['status'] ?? 'active'),
        ]);

        $roleIds = $this->resolveRoleIds($payload['role_codes'] ?? ['USER']);
        if (empty($roleIds)) {
            $roleIds = $this->resolveRoleIds(['USER']);
        }
        $this->userRepository->syncRoles($user->getId(), $roleIds);
        $freshUser = $this->requireUser($user->getId());

        $this->authLogRepository->log(
            $freshUser->getId(),
            'user_created_by_admin',
            'Utilisateur cree via le back-office.',
            $context['ip_address'] ?? null,
            $context['user_agent'] ?? null
        );

        return $this->buildUserPayload($freshUser);
    }

    public function updateUser($userId, array $payload, array $context)
    {
        $existingUser = $this->requireUser($userId);
        $username = trim((string) ($payload['username'] ?? $existingUser->getUsername()));
        $email = strtolower(trim((string) ($payload['email'] ?? $existingUser->getEmail())));

        if ($username === '' || $email === '') {
            throw new ApiException('Les champs username et email sont obligatoires.', 422, 'validation_error');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Adresse email invalide.', 422, 'validation_error');
        }

        if ($this->userRepository->usernameExists($username, $existingUser->getId())) {
            throw new ApiException('Ce nom d\'utilisateur existe deja.', 409, 'username_exists');
        }

        if ($this->userRepository->emailExists($email, $existingUser->getId())) {
            throw new ApiException('Cet email existe deja.', 409, 'email_exists');
        }

        $data = [
            'username' => $username,
            'email' => $email,
            'first_name' => $this->nullableTrim($payload, 'first_name', $existingUser->getFirstName()),
            'last_name' => $this->nullableTrim($payload, 'last_name', $existingUser->getLastName()),
            'phone' => $this->nullableTrim($payload, 'phone', $existingUser->getPhone()),
            'status' => $this->normalizeStatus($payload['status'] ?? $existingUser->getStatus()),
        ];

        if (array_key_exists('password', $payload) && trim((string) $payload['password']) !== '') {
            if (strlen((string) $payload['password']) < 8) {
                throw new ApiException('Le mot de passe doit contenir au moins 8 caracteres.', 422, 'validation_error');
            }

            $data['password_hash'] = password_hash((string) $payload['password'], PASSWORD_DEFAULT);
        }

        $user = $this->userRepository->update($existingUser->getId(), $data);

        if (array_key_exists('role_codes', $payload)) {
            $this->userRepository->syncRoles($user->getId(), $this->resolveRoleIds($payload['role_codes']));
        }

        $freshUser = $this->requireUser($user->getId());

        $this->authLogRepository->log(
            $freshUser->getId(),
            'user_updated',
            'Utilisateur mis a jour.',
            $context['ip_address'] ?? null,
            $context['user_agent'] ?? null
        );

        return $this->buildUserPayload($freshUser);
    }

    public function deleteUser($userId)
    {
        $this->requireUser($userId);
        $deleted = $this->userRepository->delete($userId);

        if (!$deleted) {
            throw new ApiException('Suppression impossible.', 500, 'delete_failed');
        }

        return [
            'message' => 'Utilisateur supprime avec succes.',
        ];
    }

    public function syncUserRoles($userId, array $payload)
    {
        $user = $this->requireUser($userId);
        $this->userRepository->syncRoles($user->getId(), $this->resolveRoleIds($payload['role_codes'] ?? []));

        return $this->buildUserPayload($this->requireUser($user->getId()));
    }

    private function buildUserPayload(User $user)
    {
        $payload = $user->toSafeArray();
        $payload['roles'] = $this->userRepository->getRoles($user->getId());
        $payload['permissions'] = $this->userRepository->getPermissions($user->getId());

        return $payload;
    }

    private function requireUser($userId)
    {
        $user = $this->userRepository->findById((int) $userId);
        if (!$user) {
            throw new ApiException('Utilisateur introuvable.', 404, 'user_not_found');
        }

        return $user;
    }

    private function resolveRoleIds($roleCodes)
    {
        if (!is_array($roleCodes)) {
            throw new ApiException('role_codes doit etre un tableau.', 422, 'validation_error');
        }

        $roleIds = [];
        foreach ($roleCodes as $roleCode) {
            $role = $this->roleRepository->findByCode((string) $roleCode);
            if (!$role) {
                throw new ApiException('Role introuvable: ' . $roleCode, 404, 'role_not_found');
            }

            $roleIds[] = $role->getId();
        }

        return array_values(array_unique($roleIds));
    }

    private function normalizeStatus($status)
    {
        $status = strtolower(trim((string) $status));
        $allowed = ['active', 'inactive', 'blocked', 'pending'];
        if (!in_array($status, $allowed, true)) {
            throw new ApiException('Statut utilisateur invalide.', 422, 'validation_error');
        }

        return $status;
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
        foreach (['search', 'status', 'role_code', 'page', 'per_page'] as $key) {
            if (array_key_exists($key, $filters) && $filters[$key] !== null && $filters[$key] !== '') {
                return true;
            }
        }

        return false;
    }
}
