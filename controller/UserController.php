<?php

require_once __DIR__ . '/../model/ApiRequest.php';
require_once __DIR__ . '/../service/UserService.php';

class UserController
{
    private $userService;

    public function __construct()
    {
        $this->userService = new \UserService();
    }

    public function index(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->userService->listUsers([
                'search' => $request->getQueryParam('search'),
                'status' => $request->getQueryParam('status'),
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
            'body' => $this->userService->getUserById($request->getRouteParam('id')),
        ];
    }

    public function store(\ApiRequest $request)
    {
        return [
            'status' => 201,
            'body' => $this->userService->createUser($request->getBody(), $request->getContext()),
        ];
    }

    public function update(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->userService->updateUser($request->getRouteParam('id'), $request->getBody(), $request->getContext()),
        ];
    }

    public function destroy(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->userService->deleteUser($request->getRouteParam('id')),
        ];
    }

    public function syncRoles(\ApiRequest $request)
    {
        return [
            'status' => 200,
            'body' => $this->userService->syncUserRoles($request->getRouteParam('id'), $request->getBody()),
        ];
    }
}
