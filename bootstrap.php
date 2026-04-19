<?php

require_once __DIR__ . '/model/EnvConfig.php';
require_once __DIR__ . '/model/Database.php';
require_once __DIR__ . '/model/ApiException.php';
require_once __DIR__ . '/model/ApiRequest.php';
require_once __DIR__ . '/model/User.php';
require_once __DIR__ . '/model/Role.php';
require_once __DIR__ . '/model/Permission.php';
require_once __DIR__ . '/repository/SchemaRepository.php';
require_once __DIR__ . '/repository/UserRepository.php';
require_once __DIR__ . '/repository/RoleRepository.php';
require_once __DIR__ . '/repository/PermissionRepository.php';
require_once __DIR__ . '/repository/AuthSessionRepository.php';
require_once __DIR__ . '/repository/AuthLogRepository.php';
require_once __DIR__ . '/repository/ApiKeyRepository.php';
require_once __DIR__ . '/service/SchemaService.php';
require_once __DIR__ . '/service/JsonResponse.php';
require_once __DIR__ . '/service/ApiRouter.php';
require_once __DIR__ . '/service/JwtService.php';
require_once __DIR__ . '/service/AntiBotService.php';
require_once __DIR__ . '/service/AuthService.php';
require_once __DIR__ . '/service/ApiKeyService.php';
require_once __DIR__ . '/service/AuthorizationMiddleware.php';
require_once __DIR__ . '/service/LogService.php';
require_once __DIR__ . '/service/UserService.php';
require_once __DIR__ . '/service/RoleService.php';
require_once __DIR__ . '/service/PermissionService.php';
require_once __DIR__ . '/controller/AuthController.php';
require_once __DIR__ . '/controller/ApiKeyController.php';
require_once __DIR__ . '/controller/LogController.php';
require_once __DIR__ . '/controller/UserController.php';
require_once __DIR__ . '/controller/RoleController.php';
require_once __DIR__ . '/controller/PermissionController.php';
require_once __DIR__ . '/controller/AdminController.php';

\EnvConfig::loadFromDirectory(__DIR__);
