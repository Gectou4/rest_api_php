<?php

declare(strict_types=1);

namespace G4\Api\Config;

use G4\Api\App\Router;

/**
 * Déclaration de toutes les routes de l'application.
 * Appelé une seule fois par Router::getInstance() lors du premier accès.
 */
class Route
{
    /** Enregistre les routes sur le Router. L'ordre d'enregistrement détermine la priorité de correspondance. */
    public static function load(Router $router): void
    {
        $router
            ->match('GET', '/user/(\d+)', fn(string $id): array => ['controller' => 'User', 'action' => 'Index', 'params' => ['id' => $id]])
            ->match('GET', '/user/(\d+)/task', fn(string $id): array => ['controller' => 'User', 'action' => 'userTask', 'params' => ['id' => $id]])
            ->match('POST|PUT', '/user/(\d+)/task/(\d+)', fn(string $userId, string $taskId): array => ['controller' => 'Task', 'action' => 'addTaskToUser', 'params' => ['userId' => $userId, 'taskId' => $taskId]])
            ->match('POST|PUT', '/task', fn(): array => ['controller' => 'Task', 'action' => 'addTask'])
            ->match('POST|PUT', '/task/(\d+)', fn(string $id): array => ['controller' => 'Task', 'action' => 'editTask', 'params' => ['id' => $id]])
            ->match('DELETE', '/task/(\d+)', fn(string $id): array => ['controller' => 'Task', 'action' => 'task', 'params' => ['id' => $id]])
            ->match('DELETE', '/user/(\d+)/task/(\d+)', fn(string $userId, string $taskId): array => ['controller' => 'Task', 'action' => 'userTask', 'params' => ['userId' => $userId, 'taskId' => $taskId]]);
    }
}
