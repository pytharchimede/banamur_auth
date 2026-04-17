<?php

require_once __DIR__ . '/../repository/SchemaRepository.php';

class SchemaService
{
    private $schemaRepository;

    public function __construct()
    {
        $this->schemaRepository = new \SchemaRepository();
    }

    public function ensureDatabaseReady()
    {
        $tables = $this->getTableDefinitions();
        $createdTables = [];

        foreach ($tables as $tableName => $sql) {
            if ($this->schemaRepository->tableExists($tableName)) {
                continue;
            }

            $this->schemaRepository->createTable($sql);
            $createdTables[] = $tableName;
        }

        $this->seedDefaultRoles();
        $this->seedDefaultPermissions();

        return [
            'database_exists' => $this->schemaRepository->databaseExists(),
            'created_tables' => $createdTables,
        ];
    }

    private function seedDefaultRoles()
    {
        $this->schemaRepository->insertRoleIfMissing('Super Administrateur', 'SUPER_ADMIN', 'Acces complet a la plateforme');
        $this->schemaRepository->insertRoleIfMissing('Administrateur', 'ADMIN', 'Administration applicative');
        $this->schemaRepository->insertRoleIfMissing('Utilisateur', 'USER', 'Acces standard utilisateur');
    }

    private function seedDefaultPermissions()
    {
        $permissions = [
            ['Lire les utilisateurs', 'user.read', 'Consulter les utilisateurs', 'users'],
            ['Creer les utilisateurs', 'user.create', 'Creer un utilisateur', 'users'],
            ['Modifier les utilisateurs', 'user.update', 'Mettre a jour un utilisateur', 'users'],
            ['Supprimer les utilisateurs', 'user.delete', 'Supprimer un utilisateur', 'users'],
            ['Lire les logs', 'log.read', 'Consulter les journaux de securite', 'logs'],
            ['Assigner les roles', 'role.assign', 'Associer des roles a un utilisateur', 'roles'],
            ['Lire les roles', 'role.read', 'Consulter les roles', 'roles'],
            ['Creer les roles', 'role.create', 'Creer un role', 'roles'],
            ['Modifier les roles', 'role.update', 'Mettre a jour un role', 'roles'],
            ['Supprimer les roles', 'role.delete', 'Supprimer un role', 'roles'],
            ['Lire les permissions', 'permission.read', 'Consulter les permissions', 'permissions'],
            ['Modifier les permissions', 'permission.update', 'Mettre a jour les permissions', 'permissions'],
            ['Assigner les permissions', 'permission.assign', 'Associer des permissions a un role', 'permissions'],
        ];

        foreach ($permissions as $permission) {
            $this->schemaRepository->insertPermissionIfMissing($permission[0], $permission[1], $permission[2], $permission[3]);
        }

        foreach (array_column($permissions, 1) as $permissionCode) {
            $this->schemaRepository->assignPermissionToRoleIfMissing('SUPER_ADMIN', $permissionCode);
            $this->schemaRepository->assignPermissionToRoleIfMissing('ADMIN', $permissionCode);
        }
    }

    private function getTableDefinitions()
    {
        return [
            'users' => "CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(190) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(100) DEFAULT NULL,
                last_name VARCHAR(100) DEFAULT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                last_login_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_users_username (username),
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'roles' => "CREATE TABLE roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_roles_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'permissions' => "CREATE TABLE permissions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(150) NOT NULL,
                description TEXT DEFAULT NULL,
                module VARCHAR(100) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_permissions_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'user_roles' => "CREATE TABLE user_roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                role_id INT UNSIGNED NOT NULL,
                assigned_by INT UNSIGNED DEFAULT NULL,
                assigned_at DATETIME NOT NULL,
                UNIQUE KEY uq_user_roles (user_id, role_id),
                CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'role_permissions' => "CREATE TABLE role_permissions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role_id INT UNSIGNED NOT NULL,
                permission_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_role_permissions (role_id, permission_id),
                CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'auth_sessions' => "CREATE TABLE auth_sessions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_auth_sessions_token_hash (token_hash),
                KEY idx_auth_sessions_user_id (user_id),
                CONSTRAINT fk_auth_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'password_resets' => "CREATE TABLE password_resets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                reset_token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                KEY idx_password_resets_user_id (user_id),
                CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'auth_logs' => "CREATE TABLE auth_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED DEFAULT NULL,
                event_type VARCHAR(100) NOT NULL,
                message VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                KEY idx_auth_logs_user_id (user_id),
                CONSTRAINT fk_auth_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }
}
