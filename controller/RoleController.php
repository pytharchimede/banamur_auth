<?php

require_once __DIR__ . '/../model/ApiRequest.php';
require_once __DIR__ . '/../service/RoleService.php';

class RoleController
{
    private $roleService;

    public function __construct()
    {
        $this->roleService = new \RoleService();
    }

    public function index(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->roleService->listRoles(),
        ];
    }

    public function show(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->roleService->getRoleById($request->getRouteParam('id')),
        ];
    }

    public function store(\ApiRequest $request)
    {
        return [
            'status' => 201,
            'body' => $this->roleService->createRole($request->getBody()),
        ];
    }

    public function update(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->roleService->updateRole($request->getRouteParam('id'), $request->getBody()),
        ];
    }

    public function destroy(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->roleService->deleteRole($request->getRouteParam('id')),
        ];
    }

    public function syncPermissions(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->roleService->syncRolePermissions($request->getRouteParam('id'), $request->getBody()),
        ];
    }

    public function permissions(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->roleService->listPermissions(),
        ];
    }
}
