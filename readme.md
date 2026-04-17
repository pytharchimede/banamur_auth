# Banamur Auth

Banamur Auth est une petite API PHP qui gere :

- l'authentification
- les utilisateurs
- les roles
- les permissions
- la protection des routes API

Ce README a ete ecrit pour un developpeur debutant.

Le but est que tu comprennes :

- a quoi sert le projet
- comment il demarre
- comment les fichiers sont organises
- comment une requete HTTP traverse le code
- quelles tables sont creees en base
- quelles routes API existent
- comment tester l'application

## 1. Idee generale du projet

Ce projet est un serveur d'authentification.

En pratique, cela veut dire qu'il sait faire plusieurs choses :

- creer un compte utilisateur
- connecter un utilisateur
- donner un jeton de connexion
- verifier si un utilisateur a le droit d'acceder a une route
- gerer les roles comme `ADMIN` ou `USER`
- gerer les permissions comme `user.read` ou `role.create`

Le projet est ecrit en PHP simple, sans framework, sans namespace.

Cela veut dire que l'application repose sur des classes PHP classiques chargees avec `require_once`.

## 2. Ce qu'il faut savoir avant de commencer

Si tu debutes, retiens surtout ces definitions :

- un utilisateur : une personne ou un compte qui se connecte
- un role : un groupe de droits, par exemple `ADMIN`
- une permission : une action precise, par exemple `user.delete`
- un token : une cle temporaire envoyee apres connexion
- une route API : une URL que le frontend ou Postman appelle
- un repository : une classe qui parle a la base de donnees
- un service : une classe qui porte la logique metier
- un controller : une classe qui recoit la requete et renvoie la reponse
- un middleware : une verification faite avant d'executer une route

## 3. Technologies utilisees

- PHP
- MySQL
- PDO pour parler a MySQL
- Apache via WAMP
- JSON pour les entrees et sorties API

## 4. Structure actuelle du projet

```text
banamur_auth/
|-- api/
|   |-- .htaccess
|   `-- index.php
|-- API.md
|-- index.php
|-- controller/
|   |-- LogController.php
|   |-- AuthController.php
|   |-- RoleController.php
|   `-- UserController.php
|-- css/
|   `-- admin-dashboard.css
|-- js/
|   `-- admin-dashboard.js
|-- model/
|   |-- ApiException.php
|   |-- ApiRequest.php
|   |-- Database.php
|   |-- EnvConfig.php
|   |-- Permission.php
|   |-- Role.php
|   `-- User.php
|-- repository/
|   |-- AuthLogRepository.php
|   |-- AuthSessionRepository.php
|   |-- PermissionRepository.php
|   |-- RoleRepository.php
|   |-- SchemaRepository.php
|   `-- UserRepository.php
|-- service/
|   |-- ApiRouter.php
|   |-- AuthorizationMiddleware.php
|   |-- AuthService.php
|   |-- JsonResponse.php
|   |-- LogService.php
|   |-- RoleService.php
|   |-- SchemaService.php
|   `-- UserService.php
|-- bootstrap.php
`-- readme.md
```

## 5. Role de chaque dossier

### `model/`

Ce dossier contient les objets simples du projet.

Exemples :

- `User.php` represente un utilisateur
- `Role.php` represente un role
- `Permission.php` represente une permission
- `ApiRequest.php` represente une requete HTTP
- `ApiException.php` represente une erreur API metier
- `Database.php` ouvre la connexion MySQL
- `EnvConfig.php` lit le fichier `.env`

### `repository/`

Ce dossier contient les classes qui font les requetes SQL.

Exemples :

- `UserRepository.php` lit et modifie la table `users`
- `RoleRepository.php` lit et modifie la table `roles`
- `PermissionRepository.php` lit la table `permissions`
- `AuthSessionRepository.php` gere les sessions de connexion
- `AuthLogRepository.php` ecrit dans les logs d'authentification
- `SchemaRepository.php` cree les tables et les donnees de base

### `service/`

Ce dossier contient la logique metier.

Exemples :

- `AuthService.php` gere inscription, connexion, verification du token et deconnexion
- `UserService.php` gere le CRUD des utilisateurs
- `RoleService.php` gere le CRUD des roles et l'association des permissions
- `AuthorizationMiddleware.php` verifie si un utilisateur a le bon role ou la bonne permission
- `ApiRouter.php` choisit quelle route doit etre executee
- `SchemaService.php` verifie si la base et les tables existent
- `JsonResponse.php` envoie les reponses JSON

### `controller/`

Ce dossier fait le lien entre la requete HTTP et les services.

Exemples :

- `AuthController.php`
- `UserController.php`
- `RoleController.php`

Un controller ne doit pas contenir beaucoup de logique SQL.

Son role est surtout de :

- recuperer la requete
- appeler le bon service
- renvoyer une reponse

### `api/`

Ce dossier contient le point d'entree HTTP.

- `index.php` est le fichier principal appele par Apache
- `.htaccess` redirige les requetes vers `index.php`

## 6. Comment l'application demarre

Quand une requete arrive sur l'API, voici le chemin global :

1. Apache envoie la requete vers `api/index.php`
2. `bootstrap.php` charge toutes les classes necessaires
3. `SchemaService` verifie la base et les tables
4. `ApiRequest` transforme la requete HTTP en objet PHP
5. `ApiRouter` cherche la bonne route
6. si la route est protegee, `AuthorizationMiddleware` verifie le token et les permissions
7. le controller est appele
8. le controller appelle un service
9. le service appelle un ou plusieurs repositories
10. les repositories parlent a MySQL
11. la reponse revient en JSON

### Schema visuel simple du flux

```text
[Client / Postman / Frontend]
     |
     v
  [api/index.php]
     |
     v
    [ApiRouter]
     |
     v
[AuthorizationMiddleware]
     |
     v
   [Controller]
     |
     v
     [Service]
     |
     v
   [Repository]
     |
     v
  [Base MySQL]
```

Explication tres simple :

- le client envoie une requete HTTP
- `api/index.php` est la porte d'entree
- `ApiRouter` choisit la bonne route
- `AuthorizationMiddleware` verifie le token et les droits si besoin
- le controller recoit la requete
- le service applique la logique metier
- le repository execute le SQL
- MySQL stocke ou renvoie les donnees

## 7. Fichier `.env`

Le projet lit sa configuration dans un fichier `.env`.

Variables attendues :

- `PROJECT_DB_HOST`
- `PROJECT_DB_NAME`
- `PROJECT_DB_USER`
- `PROJECT_DB_PASS`

Exemple simple :

```env
PROJECT_DB_HOST=localhost
PROJECT_DB_NAME=fidestci_auth_db
PROJECT_DB_USER=root
PROJECT_DB_PASS=
```

Si tu utilises WAMP, adapte `PROJECT_DB_USER` et `PROJECT_DB_PASS` selon ta configuration MySQL.

## 8. Creation automatique de la base et des tables

Un point important a comprendre :

le projet essaie de preparer la base automatiquement au demarrage.

Cela est fait par `SchemaService`.

Au lancement, il verifie :

- si la base de donnees existe
- si les tables existent
- si les roles systeme existent
- si les permissions de base existent

### Tables creees automatiquement

- `users`
- `roles`
- `permissions`
- `user_roles`
- `role_permissions`
- `auth_sessions`
- `password_resets`
- `auth_logs`

### Roles systeme ajoutes automatiquement

- `SUPER_ADMIN`
- `ADMIN`
- `USER`

### Permissions ajoutees automatiquement

- `user.read`
- `user.create`
- `user.update`
- `user.delete`
- `role.assign`
- `role.read`
- `role.create`
- `role.update`
- `role.delete`
- `permission.read`
- `permission.assign`

### Point important

Les roles `SUPER_ADMIN` et `ADMIN` recoivent automatiquement toutes les permissions ci-dessus.

En revanche, aucun utilisateur administrateur n'est cree automatiquement.

Donc au debut :

- tu peux creer un compte via `/auth/register`
- ce compte aura en general le role `USER`
- ce compte n'aura pas acces aux routes d'administration comme `/users` ou `/roles`

Pour tester tout le back-office, il faut donc promouvoir un utilisateur existant en `ADMIN` ou `SUPER_ADMIN` dans la base.

## 9. Tables de la base expliquees simplement

### `users`

Stocke les utilisateurs.

Exemples de colonnes :

- `username`
- `email`
- `password_hash`
- `status`

### `roles`

Stocke les roles.

Exemples :

- `ADMIN`
- `USER`

### `permissions`

Stocke les permissions detaillees.

Exemples :

- `user.read`
- `role.create`

### `user_roles`

Relie les utilisateurs aux roles.

Exemple :

- utilisateur 5 a le role `ADMIN`

### `role_permissions`

Relie les roles aux permissions.

Exemple :

- le role `ADMIN` a la permission `user.delete`

### `auth_sessions`

Stocke les tokens de connexion actifs.

Le token brut n'est pas stocke directement : c'est son hash qui est stocke.

### `auth_logs`

Stocke un historique des actions sensibles.

Exemples :

- inscription
- connexion reussie
- echec de connexion
- deconnexion

## 10. Documentation technique de l'API

La documentation technique complete des endpoints a ete deplacee dans un fichier dedie pour garder ce README plus lisible.

Tu y trouveras :

- la base URL
- le format des reponses JSON
- le catalogue complet des endpoints
- le detail de chaque route
- les permissions requises
- les exemples de body et les erreurs frequentes
- le rappel sur l'autorisation

Fichier a consulter :

- `API.md`

## 11. Console graphique

Une interface graphique est disponible a la racine du projet :

- `index.php`

Cette interface permet de :

- tester tous les endpoints
- gerer l'authentification visuellement
- naviguer entre des vues separees `Dashboard`, `Users`, `Roles` et `Logs`
- lister les utilisateurs
- faire de vrais ajouts et modifications sur users et roles
- filtrer et paginer les utilisateurs et les logs
- editer les utilisateurs et les roles inline directement dans les listes
- confirmer les suppressions via des modales
- consulter les logs applicatifs

Technos utilisees pour cette interface :

- Tailwind CSS via CDN
- JavaScript vanilla
- appels directs aux endpoints JSON existants

## 12. Comment tester rapidement avec Postman ou curl

### Collection Postman fournie

Une collection Postman de base est disponible dans le projet :

- `postman/banamur_auth.postman_collection.json`
- `postman/banamur_auth.postman_environment.json`

Cette collection contient les dossiers suivants :

- `Health`
- `Auth`
- `Users`
- `Roles`

Elle contient aussi des variables de collection :

- `baseUrl`
- `token`
- `userId`
- `roleId`
- `adminToken`
- `adminIdentifier`
- `adminPassword`
- `userIdentifier`
- `userPassword`

### Utilisation manuelle avec Postman

La collection sert a interroger les API manuellement, route par route.

Elle ne doit pas etre vue comme une suite de tests automatique.

Le but est surtout de :

- envoyer facilement une requete
- modifier le body JSON a la main
- changer les variables d'environnement
- lire la reponse JSON calmement
- comprendre comment se comporte l'API

### Prerequis pour utiliser toutes les requetes

Pour les routes publiques et utilisateur simple :

- aucun prerequis complexe

Pour les routes d'administration :

- il faut un utilisateur ayant le role `ADMIN` ou `SUPER_ADMIN`
- il faut renseigner `adminIdentifier` et `adminPassword` dans l'environnement Postman

### Ordre conseille pour interroger les API manuellement

1. importe la collection et l'environnement
2. choisis l'environnement `Banamur Auth Local`
3. lance `Health > Health Check`
4. modifie si besoin les variables `userIdentifier` et `userPassword`
5. lance `Auth > Register User`
6. lance `Auth > Login User`
7. copie le token retourne dans la variable `token` si necessaire
8. lance `Auth > Me`
9. si un admin existe, lance `Auth > Login Admin`
10. copie le token admin dans `adminToken`
11. interroge ensuite les routes `Users` et `Roles`

Conseil de depart :

1. importe la collection dans Postman
2. importe aussi l'environnement Postman
3. verifie la variable `baseUrl`
4. lance `Health > Health Check`
5. lance `Auth > Register User`
6. lance `Auth > Login User`
7. renseigne manuellement `token` ou `adminToken` apres login si necessaire
8. utilise ensuite les routes protegees

Attention :

les routes CRUD utilisateurs et roles demandent un compte ayant les bonnes permissions. Pour les tester completement, utilise un utilisateur promu en `ADMIN` ou `SUPER_ADMIN`.

### Test 1 : verifier que l'API repond

```bash
curl http://localhost/banamur_auth/api/health
```

### Test 2 : creer un utilisateur

```bash
curl -X POST http://localhost/banamur_auth/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "alice",
    "email": "alice@example.com",
    "password": "motdepasse123"
  }'
```

### Test 3 : se connecter

```bash
curl -X POST http://localhost/banamur_auth/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "identifier": "alice@example.com",
    "password": "motdepasse123"
  }'
```

### Test 4 : appeler une route protegee

Remplace `TON_TOKEN` par le token obtenu au login.

```bash
curl http://localhost/banamur_auth/api/auth/me \
  -H "Authorization: Bearer TON_TOKEN"
```

## 13. Premier administrateur : comment faire

Comme aucun compte `ADMIN` n'est cree automatiquement, il faut faire une petite manipulation au debut.

### Methode simple

1. cree un utilisateur avec `/api/auth/register`
2. ouvre MySQL ou phpMyAdmin
3. trouve l'identifiant de cet utilisateur dans `users`
4. trouve l'identifiant du role `ADMIN` dans `roles`
5. ajoute une ligne dans `user_roles`

Exemple SQL :

```sql
INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at)
VALUES (1, 2, NULL, NOW());
```

Attention :

les identifiants `1` et `2` ci-dessus sont seulement des exemples.

Il faut verifier les vraies valeurs dans ta base.

## 14. Comment lire le code quand on debute

Si tu veux comprendre le projet sans te perdre, lis les fichiers dans cet ordre :

1. `api/index.php`
2. `bootstrap.php`
3. `model/ApiRequest.php`
4. `service/ApiRouter.php`
5. `service/AuthorizationMiddleware.php`
6. `controller/AuthController.php`
7. `service/AuthService.php`
8. `repository/UserRepository.php`
9. `service/UserService.php`
10. `service/RoleService.php`

Pourquoi cet ordre ?

Parce qu'il suit exactement le chemin d'une requete HTTP dans l'application.

## 15. Bonnes pratiques deja appliquees dans le projet

- les mots de passe sont hashes avec `password_hash()`
- la verification du mot de passe utilise `password_verify()`
- les requetes SQL utilisent PDO et des requetes preparees
- les reponses API sont normalisees en JSON
- les routes sensibles sont protegees par token
- les droits sont controles par permissions
- les erreurs metier sont centralisees avec `ApiException`

## 16. Limitations actuelles

Le projet fonctionne deja, mais il reste des points a garder en tete :

- le frontend fourni est une console d'administration et de test, pas encore un espace utilisateur complet
- il n'y a pas encore de tests automatises
- le premier administrateur doit encore etre configure manuellement
- il n'y a pas encore de mecanisme de reset mot de passe expose en API
- il n'y a pas encore de pagination sur les listes

## 17. Si tu dois ajouter une nouvelle fonctionnalite

Exemple : tu veux ajouter une route `GET /api/profile`.

Ordre conseille :

1. ajouter la logique dans un service
2. ajouter les acces SQL dans un repository si necessaire
3. ajouter une methode dans un controller
4. declarer la route dans `api/index.php`
5. proteger la route avec `authorizeRoute(...)` si besoin
6. tester avec Postman

## 18. Resume ultra simple

Si tu dois retenir seulement l'essentiel, retiens ceci :

- `api/index.php` recoit la requete
- le routeur choisit la bonne route
- le middleware verifie le token et les permissions
- le controller appelle un service
- le service applique la logique metier
- le repository fait les requetes SQL
- la reponse repart en JSON

## 19. Prochaine amelioration utile

Pour rendre le projet encore plus simple a prendre en main, les prochaines evolutions utiles seraient :

- un script qui cree automatiquement un premier compte administrateur
- des tests automatises des routes principales
- une documentation Swagger ou OpenAPI
