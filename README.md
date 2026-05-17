# G4Api - Mini API REST

API REST légère en PHP, sans framework, avec sortie JSON (ou Markdown via `Accept: text/markdown`).

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

- PHP 8.3+
- MySQL 5.7+ / MariaDB 10.4+
- Composer

### Installation

```bash
composer install
```

### Tests

Les tests nécessitent une connexion base de données valide.

```bash
composer exec phpunit
```

### Format de réponse

Par défaut l'API répond en JSON. Pour obtenir une réponse Markdown :

```
Accept: text/markdown
```
