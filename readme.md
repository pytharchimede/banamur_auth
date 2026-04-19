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

Stocke les sessions de connexion actives.

Quand un utilisateur se connecte, l'API retourne un JWT au client.

Ce JWT est lie a une session stockee ici.

Le secret brut de session n'est pas stocke directement : c'est son hash qui est stocke.

### `api_keys`

Stocke les cles API utilisees pour les integrations developpeur.

Exemples :

- une cle API creee depuis le back-office
- une cle API rattachee a un utilisateur admin
- une cle API revoquee si elle ne doit plus fonctionner

La cle brute n'est jamais stockee en clair en base.

Comme pour les sessions, seul un hash est stocke.

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
- gerer l'authentification visuellement avec JWT
- proteger le login admin avec un controle anti-robot maison `Lagune Shield`
- generer et revoquer des cles API developpeur
- suivre separement les comptes admin et les comptes developpeur
- creer rapidement un admin ou un developpeur via des presets de formulaire
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
- `adminToken`
- `apiKey`
- `userId`
- `roleId`

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
- il faut d'abord recuperer un JWT admin ou creer une cle API admin

### Acces admin par defaut

Si aucun compte administrateur n'existait encore au demarrage, l'application cree automatiquement :

- username : `admin`
- mot de passe : `admin`
- role : `ADMIN`

Ce compte sert au premier acces au back-office.

Conseil simple : connecte-toi une premiere fois avec lui, puis change son mot de passe ou cree tout de suite un autre admin de confiance.

### Controle anti-robot du back-office

Le formulaire de connexion de `index.php` charge automatiquement un defi maison appele `Lagune Shield`.

Le principe est simple :

1. le frontend demande un defi a `GET /auth/anti-bot-challenge`
2. l'API renvoie une grille de cartes avec un code a retrouver
3. le navigateur envoie la reponse avec le login admin
4. le serveur verifie la signature du defi, sa duree de vie, l'empreinte du navigateur, le delai minimum de resolution et un champ piege anti-bot

Important :

- ce controle est fait pour le login du back-office
- les appels Postman ou curl classiques sur `POST /auth/login` continuent de fonctionner sans ce defi tant que tu n'envoies pas `login_scope=admin_console`

### Cas ultra simple 1 : route protegee par JWT

Exemple : tu veux appeler `GET /users`.

Fais exactement ceci :

1. ouvre Postman
2. importe la collection et l'environnement `Banamur Auth Local`
3. lance `Auth > Login Admin`
4. regarde la reponse JSON
5. la collection enregistre automatiquement le JWT dans `adminToken`
6. lance ensuite `Users > List Users`

Ce qui se passe en coulisse est tres simple :

- `Login Admin` appelle `POST /auth/login`
- la reponse contient un JWT
- ce JWT est envoye ensuite dans le header : `Authorization: Bearer TON_JWT`

Si tu veux le faire a la main dans Postman :

1. ouvre une requete
2. dans l'onglet `Headers`, ajoute une ligne
3. mets `Authorization` comme cle
4. mets `Bearer TON_JWT` comme valeur
5. envoie la requete

### Cas ultra simple 2 : route protegee par cle API

Exemple : tu veux qu'un developpeur appelle l'API sans refaire un login a chaque fois.

Fais exactement ceci :

1. connecte-toi dans l'interface web `index.php` avec un admin
2. dans le Dashboard, utilise le bloc `Generer une cle API`
3. copie la cle API affichee
4. dans Postman, ouvre une requete protegee
5. dans l'onglet `Headers`, ajoute `X-API-Key`
6. colle la cle API comme valeur
7. envoie la requete

Tu peux aussi utiliser les requetes Postman deja pretes :

1. lance `API Keys > Create API Key`
2. la collection stocke automatiquement la valeur dans `apiKey`
3. lance `API Keys > Me With API Key`

Le header envoye est alors simplement :

```text
X-API-Key: TA_CLE_API
```

### Comprendre ce que le dashboard affiche

Quand tu es connecte au back-office, le panneau `Session admin et gouvernance` peut afficher 3 secrets differents.

#### 1. JWT admin

Le `JWT admin` est le jeton de travail du back-office.

C'est lui qu'il faut utiliser pour :

- lister les utilisateurs
- gerer les roles
- lire les logs
- creer ou revoquer des cles API

Format d'utilisation :

```text
Authorization: Bearer TON_JWT_ADMIN
```

Exemple :

```bash
curl http://localhost/banamur_auth/api/users \
  -H "Authorization: Bearer TON_JWT_ADMIN"
```

#### 2. JWT utilisateur

Le `JWT utilisateur` est juste la reference locale du jeton associe a l'utilisateur courant.

Important pour un junior :

- si tu es connecte avec un compte admin, il est normal que `JWT admin` et `JWT utilisateur` soient parfois identiques
- cela ne veut pas dire qu'il y a deux sessions differentes
- cela veut juste dire que le meme compte sert a la fois d'utilisateur connecte et d'operateur admin

En pratique :

- garde surtout le `JWT admin` pour travailler sur le back-office
- utilise le `JWT utilisateur` seulement si tu veux tester une route utilisateur simple comme `GET /auth/me`

#### 3. Cle API developpeur

La `Cle API developpeur` affiche la derniere cle API creee depuis l'interface admin.

Elle sert surtout pour :

- Postman
- scripts shell
- integrations serveur a serveur
- petits outils internes

Format d'utilisation :

```text
X-API-Key: TA_CLE_API
```

Exemple :

```bash
curl http://localhost/banamur_auth/api/auth/me \
  -H "X-API-Key: TA_CLE_API"
```

Important :

- la cle API est pratique quand tu ne veux pas refaire un login JWT a chaque fois
- elle ne remplace pas le compte utilisateur en base : elle agit au nom d'un utilisateur cible
- elle doit etre copiee et stockee des sa creation
- elle ne doit pas etre committee dans Git

### Comment l'exploiter concretement

Cas 1 : tu es admin et tu veux administrer la plateforme

1. connecte-toi au back-office avec `admin/admin` ou un autre compte `ADMIN`
2. utilise le `JWT admin` pour tester `GET /users`, `GET /roles`, `GET /logs`, `GET /api-keys`
3. utilise ensuite le bloc `Generer une cle API` pour creer une cle pour un developpeur ou un service

Cas 2 : tu es developpeur et tu veux appeler l'API depuis Postman

1. demande a un admin de creer une cle API pour ton compte
2. copie la valeur de la cle au moment de sa creation
3. mets-la dans le header `X-API-Key`
4. teste d'abord `GET /auth/me`
5. passe ensuite aux routes autorisees par ton compte

Cas 3 : tu veux verifier rapidement qui tu es et quels droits tu utilises

1. regarde le bloc `Session`
2. lis la ligne profil et roles
3. lis la ligne `Permissions API`
4. si `generation/revocation autorisee` apparait, alors ce compte peut gerer les cles API

### Regle simple pour un developpeur junior

Utilise cette memoire courte :

- `JWT admin` = administrer l'application
- `JWT utilisateur` = tester un utilisateur connecte
- `Cle API` = automatiser des appels sans refaire un login

Si tu hesites, commence toujours par :

1. `GET /auth/me`
2. avec le secret que tu veux utiliser
3. puis regarde l'identite renvoyee et ajuste ensuite

### Scenarios Postman pas a pas

Si tu veux une version courte type check-list junior, lis aussi [guide-postman-junior.md](guide-postman-junior.md).

Les scenarios ci-dessous partent de cette hypothese simple :

1. tu as importe `postman/banamur_auth.postman_collection.json`
2. tu as importe `postman/banamur_auth.postman_environment.json`
3. tu as choisi l'environnement `Banamur Auth Local`
4. la variable `baseUrl` pointe vers `http://localhost/banamur_auth/api`

#### Scenario 1 : verifier que tout est en ligne

But : verifier que l'API repond avant de tester l'authentification.

Etapes :

1. ouvre le dossier `Health`
2. lance `Health Check`
3. verifie que la reponse est `success: true`

Resultat attendu :

- l'API repond sans authentification
- tu sais que le serveur tourne bien

#### Scenario 2 : creer un utilisateur simple puis tester son JWT utilisateur

But : comprendre le cycle register puis login utilisateur.

Etapes :

1. ouvre le dossier `Auth`
2. lance `Register`
3. modifie si besoin `username`, `email` et `password` dans le body JSON
4. verifie que la creation reussit
5. lance ensuite `Login`
6. verifie que la variable `token` est remplie
7. lance `Me`

Resultat attendu :

- `Login` renvoie un JWT utilisateur
- `Me` renvoie l'identite du compte connecte
- tu verifies concretement ce qu'un utilisateur normal peut presenter comme preuve d'authentification

#### Scenario 3 : se connecter comme admin et tester les routes d'administration

But : comprendre la difference entre `token` et `adminToken`.

Etapes :

1. ouvre le dossier `Auth`
2. lance `Login Admin`
3. verifie que la variable `adminToken` est remplie
4. lance `Auth > Me With Admin Token` si la requete existe dans ta collection, sinon duplique `Me` et remplace le header par `Bearer {{adminToken}}`
5. ouvre le dossier `Users` puis lance `List Users`
6. ouvre le dossier `Roles` puis lance `List Roles`
7. ouvre le dossier `API Keys` puis lance `List API Keys`

Resultat attendu :

- `adminToken` est le jeton principal pour administrer la plateforme
- les routes `Users`, `Roles`, `Logs` et `API Keys` deviennent testables

#### Scenario 4 : verifier qui tu es vraiment avec le secret que tu utilises

But : ne jamais travailler a l'aveugle.

Etapes :

1. prends le secret que tu veux tester
2. appelle `GET /auth/me`
3. regarde dans la reponse :
4. `user.email`
5. `user.roles`
6. `user.permissions`

Interpretation simple :

- si tu vois `ADMIN` ou `SUPER_ADMIN`, tu peux utiliser ce compte pour le back-office
- si tu vois `api_key.manage`, tu peux creer ou revoquer des cles API
- si tu ne vois pas ces droits, ne teste pas les routes admin avec ce secret

#### Scenario 5 : creer une cle API pour un developpeur

But : permettre a un developpeur de tester sans relancer un login JWT a chaque fois.

Etapes :

1. commence par `Auth > Login Admin`
2. ouvre le dossier `API Keys`
3. lance `Create API Key`
4. dans le body JSON, mets un `name` explicite
5. renseigne `user_id` si tu veux cibler un utilisateur precis
6. envoie la requete
7. copie tout de suite la valeur `plain_key`
8. stocke-la dans la variable d'environnement `apiKey`

Resultat attendu :

- la cle API est creee
- la variable `apiKey` peut maintenant etre reutilisee dans Postman

Important :

- `plain_key` n'est renvoyee qu'une seule fois
- si tu la perds, il faut en recreer une autre

#### Scenario 6 : tester une route avec une cle API

But : verifier que la cle API fonctionne vraiment.

Etapes :

1. assure-toi que la variable `apiKey` contient bien la cle complete
2. ouvre `API Keys > Me With API Key` si elle existe dans la collection
3. sinon duplique `Auth > Me`
4. retire le header `Authorization`
5. ajoute le header `X-API-Key: {{apiKey}}`
6. envoie la requete

Resultat attendu :

- la reponse identifie l'utilisateur rattache a cette cle API
- tu confirmes que la cle peut remplacer un JWT pour certains usages techniques

#### Scenario 7 : creer un utilisateur developpeur puis lui generer une cle API

But : reproduire un vrai onboarding technique.

Etapes :

1. lance `Auth > Login Admin`
2. lance `Users > Create User`
3. dans `role_codes`, mets par exemple `USER`
4. envoie la requete
5. recupere l'id de l'utilisateur cree
6. lance `API Keys > Create API Key`
7. mets cet `user_id` dans le body
8. copie la `plain_key`
9. teste ensuite `GET /auth/me` avec `X-API-Key`

Resultat attendu :

- tu crées un compte developpeur
- tu lui rattaches une cle API
- tu verifies l'usage concret de cette cle

#### Scenario 8 : modifier les roles d'un utilisateur

But : apprendre a promouvoir ou retrograder un compte.

Etapes :

1. lance `Auth > Login Admin`
2. lance `Users > List Users`
3. recupere l'id du compte cible
4. lance `Users > Sync User Roles`
5. mets par exemple :

```json
{
  "role_codes": ["USER", "ADMIN"]
}
```

1. envoie la requete
2. relance `Users > Show User` ou `Users > List Users`

Resultat attendu :

- les roles du compte sont remplaces par la liste envoyee
- tu peux verifier ensuite si ce compte a maintenant acces au back-office

#### Scenario 9 : revoquer une cle API

But : apprendre a couper un acces technique sans toucher au mot de passe.

Etapes :

1. lance `Auth > Login Admin`
2. lance `API Keys > List API Keys`
3. repere l'id de la cle a couper
4. lance `API Keys > Delete API Key`
5. envoie la requete
6. reteste ensuite une route avec cette meme cle

Resultat attendu :

- la cle est revoquee
- elle n'est plus utilisable

#### Scenario 10 : methode de diagnostic la plus simple quand tu doutes

But : ne pas perdre de temps quand un appel echoue.

Ordre de verification conseille :

1. `Health > Health Check`
2. `Auth > Me` avec le secret courant
3. verifier `roles` et `permissions`
4. seulement ensuite tester `Users`, `Roles`, `Logs` ou `API Keys`

Regle pratique :

- si `Me` echoue, inutile d'aller plus loin
- si `Me` reussit mais sans les bonnes permissions, ce n'est pas un bug Postman, c'est un probleme de droits

### Ordre conseille pour interroger les API manuellement

1. importe la collection et l'environnement
2. choisis l'environnement `Banamur Auth Local`
3. lance `Health > Health Check`
4. lance `Auth > Register`
5. lance `Auth > Login`
6. verifie que `token` a ete rempli automatiquement
7. lance `Auth > Me`
8. lance `Auth > Login Admin`
9. verifie que `adminToken` a ete rempli automatiquement
10. interroge ensuite `Users`, `Roles` et `API Keys`

Conseil de depart :

1. importe la collection dans Postman
2. importe aussi l'environnement Postman
3. verifie la variable `baseUrl`
4. lance `Health > Health Check`
5. lance `Auth > Register`
6. lance `Auth > Login`
7. teste `Auth > Me`
8. si tu veux administrer l'API, lance `Auth > Login Admin`
9. si tu veux tester sans JWT, cree ensuite une cle via `API Keys > Create API Key`

Attention :

les routes CRUD utilisateurs, roles et cles API demandent un compte ayant les bonnes permissions. Pour les tester completement, utilise un utilisateur promu en `ADMIN` ou `SUPER_ADMIN`.

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

Remplace `TON_TOKEN` par le JWT obtenu au login.

```bash
curl http://localhost/banamur_auth/api/auth/me \
  -H "Authorization: Bearer TON_TOKEN"
```

### Test 5 : appeler une route protegee avec une cle API

Remplace `TA_CLE_API` par la cle API obtenue depuis l'interface d'administration ou Postman.

```bash
curl http://localhost/banamur_auth/api/auth/me \
  -H "X-API-Key: TA_CLE_API"
```

## 13. Premier administrateur : comment faire

Le projet cree maintenant automatiquement un premier administrateur si aucun admin n'existait encore.

Identifiants de depart :

- username : `admin`
- mot de passe : `admin`

Role attribue automatiquement : `ADMIN`

### Si tu veux recreer cet acces manuellement

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
- les routes sensibles sont protegees par JWT ou cle API
- les droits sont controles par permissions
- les erreurs metier sont centralisees avec `ApiException`

## 16. Limitations actuelles

Le projet fonctionne deja, mais il reste des points a garder en tete :

- le frontend fourni est une console d'administration et de test, pas encore un espace utilisateur complet
- il n'y a pas encore de tests automatises
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

- une rotation guidee du mot de passe admin par defaut au premier login
- des tests automatises des routes principales
- une documentation Swagger ou OpenAPI
