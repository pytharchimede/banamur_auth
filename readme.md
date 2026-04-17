# Banamur Auth

Serveur d'authentification centralise pour les applications Banamur.

Ce projet a pour objectif de fournir une base commune pour la gestion des comptes utilisateurs, des roles, des permissions et des sessions d'authentification.

## Objectif

Le serveur doit permettre a l'equipe de developper un module d'authentification reutilisable avec les responsabilites suivantes :

- gestion des utilisateurs
- gestion des roles
- gestion des permissions
- attribution des roles aux utilisateurs
- authentification par identifiant et mot de passe
- gestion des sessions ou jetons
- journalisation des connexions et des actions sensibles

## Base technique actuelle

Le depot contient actuellement :

- un chargeur de configuration d'environnement dans `model/EnvConfig.php`
- une connexion PDO MySQL dans `model/Database.php`
- un espace pour les styles dans `css/`

La connexion a la base est basee sur les variables d'environnement et initialise PDO en `utf8mb4` avec mode erreur en exception.

## Structure du projet

```text
banamur_auth/
|-- css/
|-- model/
|   |-- Database.php
|   `-- EnvConfig.php
`-- readme.md
```

## Variables d'environnement

Le projet attend un fichier `.env` a la racine du projet ou dans un dossier parent detecte par `EnvConfig`.

Variables utilisees :

- `PROJECT_DB_HOST`
- `PROJECT_DB_NAME`
- `PROJECT_DB_USER`
- `PROJECT_DB_PASS`

Exemple :

```env
PROJECT_DB_HOST=localhost
PROJECT_DB_NAME=banamur_auth
PROJECT_DB_USER=root
PROJECT_DB_PASS=
```

## Cible fonctionnelle

Le serveur d'auth doit couvrir au minimum les cas suivants :

1. creer un utilisateur
2. connecter un utilisateur
3. deconnecter un utilisateur
4. verifier qu'un utilisateur est actif
5. affecter un ou plusieurs roles a un utilisateur
6. verifier les permissions d'un utilisateur
7. reinitialiser un mot de passe
8. tracer les connexions et les echecs d'authentification

## Schema de base de donnees propose

Les tables ci-dessous constituent une base saine pour le demarrage du serveur.

### 1. `users`

Contient les comptes applicatifs.

Champs recommandes :

- `id`
- `username`
- `email`
- `password_hash`
- `first_name`
- `last_name`
- `phone`
- `status` : `active`, `inactive`, `blocked`, `pending`
- `last_login_at`
- `created_at`
- `updated_at`

Contraintes recommandees :

- `username` unique
- `email` unique
- ne jamais stocker de mot de passe en clair

### 2. `roles`

Contient les roles metier ou techniques.

Champs recommandes :

- `id`
- `name`
- `code`
- `description`
- `created_at`
- `updated_at`

Exemples de roles :

- `SUPER_ADMIN`
- `ADMIN`
- `MANAGER`
- `USER`

### 3. `permissions`

Contient les permissions fines accordeables a un role.

Champs recommandes :

- `id`
- `name`
- `code`
- `description`
- `module`
- `created_at`

Exemples de permissions :

- `user.create`
- `user.read`
- `user.update`
- `user.delete`
- `role.assign`
- `auth.login`

### 4. `user_roles`

Table de liaison entre utilisateurs et roles.

Champs recommandes :

- `id`
- `user_id`
- `role_id`
- `assigned_by`
- `assigned_at`

Contraintes recommandees :

- cle unique sur le couple `user_id`, `role_id`
- cles etrangeres vers `users.id` et `roles.id`

### 5. `role_permissions`

Table de liaison entre roles et permissions.

Champs recommandes :

- `id`
- `role_id`
- `permission_id`
- `created_at`

Contraintes recommandees :

- cle unique sur le couple `role_id`, `permission_id`

### 6. `auth_sessions` ou `refresh_tokens`

Permet de gerer les connexions persistantes.

Champs recommandes :

- `id`
- `user_id`
- `token` ou `token_hash`
- `ip_address`
- `user_agent`
- `expires_at`
- `revoked_at`
- `created_at`

Bonne pratique :

- stocker un hash du jeton plutot que le jeton brut

### 7. `password_resets`

Permet de gerer les demandes de reinitialisation.

Champs recommandes :

- `id`
- `user_id`
- `reset_token` ou `reset_token_hash`
- `expires_at`
- `used_at`
- `created_at`

### 8. `auth_logs`

Journal des actions sensibles liees a l'authentification.

Champs recommandes :

- `id`
- `user_id`
- `event_type`
- `message`
- `ip_address`
- `user_agent`
- `created_at`

Exemples d'evenements :

- connexion reussie
- echec de connexion
- deconnexion
- mot de passe modifie
- compte bloque
- role affecte

## Relations metier

- un utilisateur peut avoir plusieurs roles
- un role peut etre attribue a plusieurs utilisateurs
- un role peut posseder plusieurs permissions
- une permission peut etre partagee par plusieurs roles
- un utilisateur peut avoir plusieurs sessions

## Ordre recommande de developpement

1. finaliser le schema SQL
2. creer les modeles d'acces aux tables principales
3. implementer l'inscription ou la creation de compte admin
4. implementer la connexion et la verification de mot de passe
5. implementer les middlewares d'autorisation par role et permission
6. ajouter la gestion des sessions ou JWT
7. ajouter les logs d'authentification
8. ajouter les tests fonctionnels

## Regles de securite

- utiliser `password_hash()` et `password_verify()` pour les mots de passe
- ne jamais exposer le hash de mot de passe dans les reponses API
- valider et filtrer toutes les entrees utilisateur
- utiliser des requetes preparees PDO partout
- limiter les tentatives de connexion si possible
- journaliser les echecs de connexion
- definir une duree de vie claire pour les sessions et les jetons
- proteger les routes d'administration

## Convention de travail equipe

- toute nouvelle table doit etre documentee dans ce README ou dans une documentation SQL dediee
- toute route d'authentification doit preciser son entree, sa sortie et ses erreurs possibles
- les noms de roles et permissions doivent rester stables pour eviter les regressions
- les secrets ne doivent jamais etre pushes dans le depot

## Routes API envisagees

Exemples de routes a prevoir :

- `POST /auth/login`
- `POST /auth/logout`
- `POST /auth/refresh`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /users`
- `POST /users`
- `PUT /users/{id}`
- `GET /roles`
- `POST /roles`
- `POST /users/{id}/roles`

## Prochaine etape recommandee

Le prochain livrable utile pour l'equipe est la creation du script SQL initial avec les tables `users`, `roles`, `permissions`, `user_roles`, `role_permissions`, `auth_sessions`, `password_resets` et `auth_logs`.
