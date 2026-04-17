# Documentation API Banamur Auth

Ce fichier regroupe la documentation technique de l'API pour eviter d'alourdir le README principal.

## 1. Vue d'ensemble

Toutes les routes repondent en JSON.

Base URL en local :

```text
http://localhost/banamur_auth/api
```

### Format general des reponses

Quand tout se passe bien :

```json
{
  "success": true,
  "data": {}
}
```

Quand une erreur survient :

```json
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Message lisible pour le developpeur."
  }
}
```

### Authentification

Les routes protegees attendent cet en-tete HTTP :

```text
Authorization: Bearer TON_TOKEN
```

Le token est obtenu apres un appel reussi a `POST /auth/login`.

## 2. Catalogue rapide des endpoints

| Methode | Endpoint | Protection | Permission requise |
| --- | --- | --- | --- |
| GET | /health | publique | aucune |
| POST | /auth/register | publique | aucune |
| POST | /auth/login | publique | aucune |
| POST | /auth/logout | token | aucune |
| GET | /auth/me | token | aucune |
| GET | /users | token | user.read |
| GET | /users/{id} | token | user.read |
| POST | /users | token | user.create |
| PUT | /users/{id} | token | user.update |
| DELETE | /users/{id} | token | user.delete |
| PUT | /users/{id}/roles | token | role.assign |
| GET | /roles | token | role.read |
| GET | /roles/{id} | token | role.read |
| POST | /roles | token | role.create |
| PUT | /roles/{id} | token | role.update |
| DELETE | /roles/{id} | token | role.delete |
| PUT | /roles/{id}/permissions | token | permission.assign |
| GET | /permissions | token | permission.read |

## 3. Detail des endpoints

### 3.1 Sante de l'API

#### `GET /health`

But : verifier que l'API repond.

Authentification : aucune.

Exemple de reponse :

```json
{
  "success": true,
  "data": {
    "message": "API Banamur Auth disponible."
  }
}
```

### 3.2 Authentification

#### `POST /auth/register`

But : creer un compte utilisateur standard.

Authentification : aucune.

Body JSON attendu :

```json
{
  "username": "alice",
  "email": "alice@example.com",
  "password": "motdepasse123",
  "first_name": "Alice",
  "last_name": "Banamur",
  "phone": "+243000000000"
}
```

Champs obligatoires :

- `username`
- `email`
- `password`

Regles utiles :

- `email` doit etre valide
- `password` doit contenir au moins 8 caracteres
- `username` doit etre unique
- `email` doit etre unique
- le role `USER` est attribue automatiquement

Reponse de succes : `201 Created`

Exemple de reponse :

```json
{
  "success": true,
  "data": {
    "message": "Utilisateur cree avec succes.",
    "user": {
      "id": 1,
      "username": "alice",
      "email": "alice@example.com",
      "status": "active",
      "roles": [
        {
          "id": 3,
          "name": "Utilisateur",
          "code": "USER",
          "description": "Acces standard utilisateur"
        }
      ]
    }
  }
}
```

Erreurs frequentes :

- `422 validation_error`
- `409 username_exists`
- `409 email_exists`

#### `POST /auth/login`

But : connecter un utilisateur et retourner un token Bearer.

Authentification : aucune.

Body JSON attendu :

```json
{
  "identifier": "alice@example.com",
  "password": "motdepasse123"
}
```

`identifier` peut etre l'email ou le username.

Reponse de succes : `200 OK`

Exemple de reponse :

```json
{
  "success": true,
  "data": {
    "message": "Connexion reussie.",
    "token": "TOKEN_ICI",
    "token_type": "Bearer",
    "expires_at": "2026-04-18 12:00:00",
    "user": {
      "id": 1,
      "username": "alice",
      "email": "alice@example.com",
      "status": "active",
      "roles": [
        {
          "id": 3,
          "name": "Utilisateur",
          "code": "USER",
          "description": "Acces standard utilisateur"
        }
      ]
    }
  }
}
```

Erreurs frequentes :

- `422 validation_error`
- `401 invalid_credentials`
- `403 inactive_user`

#### `GET /auth/me`

But : recuperer l'utilisateur associe au token courant.

Authentification : token requis.

Header requis :

```text
Authorization: Bearer TON_TOKEN
```

Reponse de succes : `200 OK`

Contenu retourne :

- message
- objet `user`
- objet `session` avec `created_at` et `expires_at`

Erreurs frequentes :

- `401 missing_token`
- `401 invalid_token`
- `404 user_not_found`

#### `POST /auth/logout`

But : revoquer le token courant.

Authentification : token requis.

Header requis :

```text
Authorization: Bearer TON_TOKEN
```

Reponse de succes : `200 OK`

```json
{
  "success": true,
  "data": {
    "message": "Deconnexion reussie."
  }
}
```

Erreurs frequentes :

- `401 missing_token`
- `401 invalid_token`

### 3.3 Utilisateurs

Toutes les routes ci-dessous demandent un token valide.

#### `GET /users`

Permission requise : `user.read`

But : lister tous les utilisateurs.

Reponse de succes : `200 OK`

Chaque utilisateur retourne notamment :

- ses informations principales
- ses `roles`
- ses `permissions`

#### `GET /users/{id}`

Permission requise : `user.read`

But : recuperer un utilisateur par son identifiant.

Parametre d'URL :

- `id` : identifiant numerique de l'utilisateur

Erreurs frequentes :

- `404 user_not_found`

#### `POST /users`

Permission requise : `user.create`

But : creer un utilisateur depuis le back-office.

Body JSON possible :

```json
{
  "username": "bob",
  "email": "bob@example.com",
  "password": "motdepasse123",
  "first_name": "Bob",
  "last_name": "User",
  "phone": "+243000000001",
  "status": "active",
  "role_codes": ["USER"]
}
```

Champs obligatoires :

- `username`
- `email`
- `password`

Valeurs autorisees pour `status` :

- `active`
- `inactive`
- `blocked`
- `pending`

Si `role_codes` est absent, l'utilisateur recoit `USER`.

Erreurs frequentes :

- `422 validation_error`
- `404 role_not_found`
- `409 username_exists`
- `409 email_exists`

#### `PUT /users/{id}`

Permission requise : `user.update`

But : modifier un utilisateur existant.

Body JSON possible :

```json
{
  "username": "bob",
  "email": "bob@example.com",
  "password": "nouveauMotdepasse123",
  "first_name": "Bob Updated",
  "last_name": "User Updated",
  "phone": "+243000000009",
  "status": "active",
  "role_codes": ["ADMIN"]
}
```

Notes importantes :

- `password` est optionnel
- si `password` est fourni, il doit avoir au moins 8 caracteres
- si `role_codes` est fourni, les roles sont remplaces par la nouvelle liste

Erreurs frequentes :

- `404 user_not_found`
- `404 role_not_found`
- `422 validation_error`
- `409 username_exists`
- `409 email_exists`

#### `DELETE /users/{id}`

Permission requise : `user.delete`

But : supprimer un utilisateur.

Reponse de succes :

```json
{
  "success": true,
  "data": {
    "message": "Utilisateur supprime avec succes."
  }
}
```

Erreurs frequentes :

- `404 user_not_found`
- `500 delete_failed`

#### `PUT /users/{id}/roles`

Permission requise : `role.assign`

But : remplacer les roles d'un utilisateur.

Body JSON attendu :

```json
{
  "role_codes": ["USER", "ADMIN"]
}
```

Notes importantes :

- `role_codes` doit etre un tableau
- la liste remplace l'etat actuel
- une liste vide retire tous les roles de l'utilisateur

Erreurs frequentes :

- `404 user_not_found`
- `404 role_not_found`
- `422 validation_error`

### 3.4 Roles et permissions

Toutes les routes ci-dessous demandent un token valide.

#### `GET /roles`

Permission requise : `role.read`

But : lister les roles avec leurs permissions.

#### `GET /roles/{id}`

Permission requise : `role.read`

But : recuperer un role par son id.

Erreurs frequentes :

- `404 role_not_found`

#### `POST /roles`

Permission requise : `role.create`

But : creer un nouveau role.

Body JSON possible :

```json
{
  "name": "Manager",
  "code": "MANAGER",
  "description": "Role de demonstration",
  "permission_codes": ["user.read", "role.read"]
}
```

Champs obligatoires :

- `name`
- `code`

Notes importantes :

- `code` est converti en majuscules
- `code` doit etre unique
- `permission_codes` est optionnel

Erreurs frequentes :

- `422 validation_error`
- `409 role_code_exists`
- `404 permission_not_found`

#### `PUT /roles/{id}`

Permission requise : `role.update`

But : modifier un role existant.

Body JSON possible :

```json
{
  "name": "Manager Updated",
  "code": "MANAGER",
  "description": "Role de demonstration modifie",
  "permission_codes": ["user.read", "user.update", "role.read"]
}
```

Notes importantes :

- si `permission_codes` est fourni, la liste des permissions est remplacee
- les roles systeme `SUPER_ADMIN`, `ADMIN` et `USER` existent deja
- le code d'un role systeme ne peut pas etre modifie

Erreurs frequentes :

- `404 role_not_found`
- `409 role_code_exists`
- `404 permission_not_found`
- `422 protected_role`

#### `DELETE /roles/{id}`

Permission requise : `role.delete`

But : supprimer un role non systeme.

Notes importantes :

- `SUPER_ADMIN`, `ADMIN` et `USER` ne peuvent pas etre supprimes

Erreurs frequentes :

- `404 role_not_found`
- `422 protected_role`
- `500 delete_failed`

#### `PUT /roles/{id}/permissions`

Permission requise : `permission.assign`

But : remplacer les permissions d'un role.

Body JSON attendu :

```json
{
  "permission_codes": ["user.read", "user.update", "role.read"]
}
```

Notes importantes :

- `permission_codes` doit etre un tableau
- la liste remplace les permissions existantes
- une liste vide retire toutes les permissions du role

Erreurs frequentes :

- `404 role_not_found`
- `404 permission_not_found`
- `422 validation_error`

#### `GET /permissions`

Permission requise : `permission.read`

But : lister toutes les permissions disponibles.

Reponse de succes : `200 OK`

Exemples de codes retournes :

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

## 4. Comment fonctionne l'autorisation

L'autorisation est geree par `AuthorizationMiddleware`.

Son travail est simple :

1. lire le token Bearer
2. demander a `AuthService` d'identifier l'utilisateur
3. recuperer ses roles et ses permissions
4. verifier que la route demandee est autorisee

Regle speciale :

si l'utilisateur a le role `SUPER_ADMIN`, il passe partout.
