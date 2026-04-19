<?php

require_once __DIR__ . '/../model/ApiRequest.php';
require_once __DIR__ . '/../service/UserService.php';
require_once __DIR__ . '/../service/RoleService.php';
require_once __DIR__ . '/../service/PermissionService.php';
require_once __DIR__ . '/../service/LogService.php';
require_once __DIR__ . '/../service/ApiKeyService.php';

class AdminController
{
    private $userService;
    private $roleService;
    private $permissionService;
    private $logService;
    private $apiKeyService;

    public function __construct()
    {
        $this->userService = new \UserService();
        $this->roleService = new \RoleService();
        $this->permissionService = new \PermissionService();
        $this->logService = new \LogService();
        $this->apiKeyService = new \ApiKeyService();
    }

    public function bootstrap(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => [
                'user' => $request->getAuthenticatedIdentity()['user'],
                'users' => $this->userService->listUsers(),
                'roles' => $this->roleService->listRoles(),
                'permissions' => $this->permissionService->listPermissions(),
                'logs' => $this->logService->listLogs([
                    'limit' => $request->getQueryParam('log_limit') ?? 100,
                ]),
                'api_keys' => $this->apiKeyService->listKeys(),
            ],
        ];
    }
}
