<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$schemaService = new \SchemaService();
$schemaService->ensureDatabaseReady();

$request = \ApiRequest::fromGlobals(getApiRoute());
$router = new \ApiRouter();
$authController = new \AuthController();
$userController = new \UserController();
$roleController = new \RoleController();
$authorizationMiddleware = new \AuthorizationMiddleware();

$router->add('GET', '/health', function () use ($authController) {
    return $authController->health();
});
$router->add('POST', '/auth/register', function ($request) use ($authController) {
    return $authController->register($request);
});
$router->add('POST', '/auth/login', function ($request) use ($authController) {
    return $authController->login($request);
});
$router->add('POST', '/auth/logout', authorizeRoute($authorizationMiddleware, function ($request) use ($authController) {
    return $authController->logout($request);
}));
$router->add('GET', '/auth/me', authorizeRoute($authorizationMiddleware, function ($request) use ($authController) {
    return $authController->me($request);
}));

$router->add('GET', '/users', authorizeRoute($authorizationMiddleware, function ($request) use ($userController) {
    return $userController->index($request);
}, ['permissions' => ['user.read']]));
$router->add('GET', '/users/{id}', authorizeRoute($authorizationMiddleware, function ($request) use ($userController) {
    return $userController->show($request);
}, ['permissions' => ['user.read']]));
$router->add('POST', '/users', authorizeRoute($authorizationMiddleware, function ($request) use ($userController) {
    return $userController->store($request);
}, ['permissions' => ['user.create']]));
$router->add('PUT', '/users/{id}', authorizeRoute($authorizationMiddleware, function ($request) use ($userController) {
    return $userController->update($request);
}, ['permissions' => ['user.update']]));
$router->add('DELETE', '/users/{id}', authorizeRoute($authorizationMiddleware, function ($request) use ($userController) {
    return $userController->destroy($request);
}, ['permissions' => ['user.delete']]));
$router->add('PUT', '/users/{id}/roles', authorizeRoute($authorizationMiddleware, function ($request) use ($userController) {
    return $userController->syncRoles($request);
}, ['permissions' => ['role.assign']]));

$router->add('GET', '/roles', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->index($request);
}, ['permissions' => ['role.read']]));
$router->add('GET', '/roles/{id}', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->show($request);
}, ['permissions' => ['role.read']]));
$router->add('POST', '/roles', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->store($request);
}, ['permissions' => ['role.create']]));
$router->add('PUT', '/roles/{id}', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->update($request);
}, ['permissions' => ['role.update']]));
$router->add('DELETE', '/roles/{id}', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->destroy($request);
}, ['permissions' => ['role.delete']]));
$router->add('PUT', '/roles/{id}/permissions', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->syncPermissions($request);
}, ['permissions' => ['permission.assign']]));
$router->add('GET', '/permissions', authorizeRoute($authorizationMiddleware, function ($request) use ($roleController) {
    return $roleController->permissions($request);
}, ['permissions' => ['permission.read']]));

try {
    $response = $router->dispatch($request);
    \JsonResponse::send($response['status'] ?? 200, [
        'success' => true,
        'data' => $response['body'] ?? $response,
    ]);
} catch (\ApiException $exception) {
    \JsonResponse::send($exception->getStatusCode(), [
        'success' => false,
        'error' => [
            'code' => $exception->getErrorCode(),
            'message' => $exception->getMessage(),
        ],
    ]);
} catch (Throwable $exception) {
    \JsonResponse::send(500, [
        'success' => false,
        'error' => [
            'code' => 'internal_error',
            'message' => 'Erreur interne du serveur.',
            'details' => $exception->getMessage(),
        ],
    ]);
}

function getApiRoute()
{
    $requestUriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'));
    $normalizedScriptDirectory = rtrim($scriptDirectory, '/');

    if ($normalizedScriptDirectory !== '' && strpos($requestUriPath, $normalizedScriptDirectory) === 0) {
        $requestUriPath = substr($requestUriPath, strlen($normalizedScriptDirectory));
    }

    $route = '/' . ltrim((string) $requestUriPath, '/');

    return $route === '//' ? '/' : $route;
}

function authorizeRoute($middleware, callable $handler, array $requirements = [])
{
    return function ($request) use ($middleware, $handler, $requirements) {
        $middleware->authorize($request, $requirements);

        return $handler($request);
    };
}
