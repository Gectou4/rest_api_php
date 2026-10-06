# G4Api - Mini API REST

API REST légère en PHP, sans framework, avec sortie JSON (ou Markdown via `Accept: text/markdown`).

[![CI](https://github.com/Gectou4/rest_api_php/actions/workflows/ci.yml/badge.svg)](https://github.com/Gectou4/rest_api_php/actions/workflows/ci.yml)

> **Part of the G4Api series.** The same small API (users, tasks and their N:N link) built in several stacks, to compare ecosystems: language, tooling, tests, static analysis and CI. Learning project. The PHP version is the reference: written by hand, then polished with AI-assisted review. The other stacks were ported from it in May 2026 with the help of an AI coding assistant.
>
> | Stack                | Repository                                                              |
> | -------------------- | ----------------------------------------------------------------------- |
> | PHP 8 (no framework) | [rest_api_php](https://github.com/Gectou4/rest_api_php) (this repo)     |
> | Go                   | [rest_api_go](https://github.com/Gectou4/rest_api_go)                   |
> | Rust (axum, sqlx)    | [rest_api_rs](https://github.com/Gectou4/rest_api_rs)                   |
> | Java 21 (Jersey)     | [rest_api_java](https://github.com/Gectou4/rest_api_java)               |
> | .NET 8 (Dapper)      | [rest_api_netcsharp](https://github.com/Gectou4/rest_api_netcsharp)     |
> | Python (Flask)       | [rest_api_python](https://github.com/Gectou4/rest_api_python)           |
> | Node.js (Express)    | [rest_api_nodejs](https://github.com/Gectou4/rest_api_nodejs)           |
> | React front-end      | [rest_api_front_react](https://github.com/Gectou4/rest_api_front_react) |

Elle présente un exemple où on gère deux types d'objets et leurs relations :

| Objet  | Champs |
|--------|--------|
| `User` | `user_id`, `name`, `email` |
| `Task` | `task_id`, `title`, `description`, `creation_date`, `status` |

Les statuts de tâche (`status`) sont des entiers : `1` Backlog · `2` Todo · `3` In Progress · `4` Done · `5` Closed.

### Endpoints

| Méthode | URI | Description |
|---------|-----|-------------|
| `GET` | `/user/{id}` | Données d'un utilisateur |
| `GET` | `/user/{id}/task` | Liste des tâches d'un utilisateur |
| `POST` | `/task` | Créer une nouvelle tâche |
| `POST` / `PUT` | `/user/{id}/task/{taskId}` | Associer une tâche à un utilisateur |
| `DELETE` | `/task/{id}` | Supprimer une tâche |
| `DELETE` | `/user/{id}/task/{taskId}` | Retirer l'association tâche ↔ utilisateur |
| `POST` / `PUT` | `/task/{id}` | Modifier une tâche existante |

> L'API est conçue pour évoluer : nouveaux attributs et nouveaux endpoints peuvent être ajoutés sans rupture.

---

## Configuration

### Base de données

Créer une base MySQL et importer le schéma disponible dans `share/sql/rest_api.sql`.

Configurer ensuite la connexion via des variables d'environnement (recommandé) ou en éditant `src/Config/DB.php` :

```php
// Via variables d'environnement (recommandé)
DB_USER=root
DB_PWD=
DB_DSN=mysql:host=localhost;dbname=rest_api;charset=utf8

// Ou directement dans src/Config/DB.php
'user' => 'root',
'pwd'  => '',
'dsn'  => 'mysql:host=localhost;dbname=rest_api;charset=utf8',
```

### Routes

Les routes sont déclarées dans `src/Config/Route.php` sous forme de chaîne de `->match()`.
L'ordre de déclaration est significatif : la première route correspondante est retenue.

```php
->match(
    'POST|PUT',
    '/task/(\d+)',
    function (string $id): array {
        return [
            'controller' => 'Task',
            'action'     => 'editTask',
            'params'     => ['id' => $id],
        ];
    }
)
```

- **1er arg** : méthode(s) HTTP séparées par `|`
- **2e arg** : pattern regex de l'URI
- **3e arg** : closure retournant `controller`, `action` et `params` optionnels

### Ajouter un contrôleur

Créer une classe dans `src/Controller/` qui étend `ControllerAbstract` :

```php
namespace G4\Api\Controller;

class MyClass extends ControllerAbstract
{
    public function getIndexAction(): mixed
    {
        return ['message' => 'Hello World'];
    }
}
```

---

## Développement

### Prérequis

- PHP 8.3+ **ou** Docker
- MySQL 5.7+ / MariaDB 10.4+ **ou** Docker Compose
- Composer

### Installation locale

```bash
composer install
```

### Lancement local

```bash
php -S localhost:8000 -t public
```

### Avec Docker (recommandé)

```bash
# Lancer l'API + MySQL
docker compose up -d

# Voir les logs
docker compose logs -f app

# Arrêter
docker compose down
```

L'API est accessible sur `http://localhost:8080`.

### Tests

**Local** (nécessite une DB MySQL locale) :

```bash
composer exec phpunit
```

**Avec Docker** (isolé, pas besoin de MySQL local) :

```bash
docker compose --profile test up test
```

### Format de réponse

Par défaut l'API répond en JSON. Pour obtenir une réponse Markdown :

```
Accept: text/markdown
```
