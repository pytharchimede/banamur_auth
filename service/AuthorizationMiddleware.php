<?php

require_once __DIR__ . '/../service/AuthService.php';

class AuthorizationMiddleware
{
    private $authService;

    public function __construct()
    {
        $this->authService = new \AuthService();
    }

    public function authorize(\ApiRequest $request, array $requirements = [])
    {
        $identity = $this->authService->authenticateToken($request->getBearerToken());
        $request->setAuthenticatedIdentity($identity);

        $roleCodes = $identity['role_codes'];
        $permissionCodes = $identity['permission_codes'];

        if (in_array('SUPER_ADMIN', $roleCodes, true)) {
            return $identity;
        }

        if (!empty($requirements['roles']) && empty(array_intersect($requirements['roles'], $roleCodes))) {
            throw new \ApiException('Role insuffisant pour acceder a cette ressource.', 403, 'forbidden_role');
        }

        if (!empty($requirements['permissions']) && empty(array_intersect($requirements['permissions'], $permissionCodes))) {
            throw new \ApiException('Permission insuffisante pour acceder a cette ressource.', 403, 'forbidden_permission');
        }

        return $identity;
    }
}
