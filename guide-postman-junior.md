# Aide-memoire Postman Junior

Objectif : tester vite l'API sans te perdre dans tous les cas possibles.

## Avant de commencer

1. importe `postman/banamur_auth.postman_collection.json`
2. importe `postman/banamur_auth.postman_environment.json`
3. choisis l'environnement `Banamur Auth Local`
4. verifie que `baseUrl = http://localhost/banamur_auth/api`

## Regle simple

- `token` = JWT utilisateur
- `adminToken` = JWT admin
- `apiKey` = cle API

Si tu doutes : teste toujours `GET /auth/me` en premier.

## Check-list express

### 1. Verifier que le serveur repond

1. ouvre `Health`
2. lance `Health Check`
3. attends `success: true`

### 2. Tester un utilisateur simple

1. ouvre `Auth`
2. lance `Register`
3. adapte `username`, `email`, `password`
4. lance `Login`
5. verifie que `token` est rempli
6. lance `Me`

Tu confirmes ici :

- le compte existe
- son JWT fonctionne

### 3. Tester l'administration

1. ouvre `Auth`
2. lance `Login Admin`
3. verifie que `adminToken` est rempli
4. lance `Users > List Users`
5. lance `Roles > List Roles`
6. lance `API Keys > List API Keys`

Si une route admin echoue :

1. relance `Auth > Me` avec `Bearer {{adminToken}}`
2. regarde `roles`
3. regarde `permissions`

### 4. Creer une cle API

1. reste connecte en admin
2. ouvre `API Keys`
3. lance `Create API Key`
4. mets un `name`
5. mets `user_id` si tu veux cibler un autre compte
6. envoie la requete
7. copie tout de suite `plain_key`
8. stocke-la dans `apiKey`

Important :

- `plain_key` n'apparait qu'une seule fois
- si tu la perds, il faut recreer une cle

### 5. Tester avec une cle API

1. duplique `Auth > Me` si besoin
2. retire `Authorization`
3. ajoute `X-API-Key: {{apiKey}}`
4. envoie la requete

Tu confirmes ici :

- a quel utilisateur la cle appartient
- si la cle marche encore

### 6. Modifier les roles d'un utilisateur

1. connecte-toi en admin
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

### 7. Revoquer une cle API

1. connecte-toi en admin
2. lance `API Keys > List API Keys`
3. repere l'id de la cle
4. lance `API Keys > Delete API Key`
5. envoie la requete
6. reteste ensuite `GET /auth/me` avec cette meme cle

## Quand tu es bloque

Ordre de diagnostic :

1. `Health Check`
2. `Auth > Me`
3. verifier `roles`
4. verifier `permissions`
5. seulement ensuite tester `Users`, `Roles`, `Logs`, `API Keys`

## Lecture rapide du dashboard

- `JWT admin` : pour administrer l'application
- `JWT utilisateur` : pour tester un utilisateur connecte
- `Cle API developpeur` : pour automatiser des appels sans refaire un login

Si `JWT admin` et `JWT utilisateur` sont identiques pour `admin/admin`, c'est normal.
