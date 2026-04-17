<?php

require_once __DIR__ . '/../model/ApiRequest.php';
require_once __DIR__ . '/../service/PermissionService.php';

class PermissionController
{
    private $permissionService;

    public function __construct()
    {
        $this->permissionService = new \PermissionService();
    }

    public function index(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->permissionService->listPermissions([
                'search' => $request->getQueryParam('search'),
                'module' => $request->getQueryParam('module'),
                'role_code' => $request->getQueryParam('role_code'),
                'page' => $request->getQueryParam('page'),
                'per_page' => $request->getQueryParam('per_page'),
            ]),
        ];
    }

    public function show(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->permissionService->getPermissionById($request->getRouteParam('id')),
        ];
    }

    public function update(\ApiRequest $request)
    {
        $context = $request->getContext();
        $identity = $request->getAuthenticatedIdentity();
        $context['authenticated_user_id'] = $identity['user']['id'] ?? null;

        return [
            'status' => 200,
            'body' => $this->permissionService->updatePermission($request->getRouteParam('id'), $request->getBody(), $context),
        ];
    }
}
