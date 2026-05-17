<?php
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
            ->match('GET', '/user/(\d+)', function (string $id): array {
                return ['controller' => 'User', 'action' => 'Index', 'params' => ['id' => $id]];
            })
            ->match('GET', '/user/(\d+)/task', function (string $id): array {
                return ['controller' => 'User', 'action' => 'userTask', 'params' => ['id' => $id]];
            })
            ->match('POST|PUT', '/user/(\d+)/task/(\d+)', function (string $userId, string $taskId): array {
                return ['controller' => 'Task', 'action' => 'addTaskToUser', 'params' => ['userId' => $userId, 'taskId' => $taskId]];
            })
            ->match('POST|PUT', '/task', function (): array {
                return ['controller' => 'Task', 'action' => 'addTask'];
            })
            ->match('POST|PUT', '/task/(\d+)', function (string $id): array {
                return ['controller' => 'Task', 'action' => 'editTask', 'params' => ['id' => $id]];
            })
            ->match('DELETE', '/task/(\d+)', function (string $id): array {
                return ['controller' => 'Task', 'action' => 'task', 'params' => ['id' => $id]];
            })
            ->match('DELETE', '/user/(\d+)/task/(\d+)', function (string $userId, string $taskId): array {
                return ['controller' => 'Task', 'action' => 'userTask', 'params' => ['userId' => $userId, 'taskId' => $taskId]];
            });
    }
}
