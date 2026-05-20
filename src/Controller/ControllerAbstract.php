<?php

declare(strict_types=1);

namespace G4\Api\Controller;

use G4\Api\Model\Task;
use G4\Api\Model\User;

/** Contrôleur de base : gestion des paramètres de route et du code HTTP de réponse. */
abstract class ControllerAbstract implements ControllerInterface
{
    protected array $params = [];

    protected int $code = 200;

    abstract public function getIndexAction(): mixed;

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Retourne la valeur d'un paramètre de route ou de requête.
     * Les paramètres proviennent à la fois des segments d'URI (route) et du corps de la requête (args).
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * Vérifie l'existence d'un utilisateur et le retourne.
     * @throws \Exception si l'utilisateur n'existe pas (code 404).
     */
    protected function requireUser(int $id): User
    {
        $user = new User($id);
        if (!$user->isLoaded()) {
            $this->setCode(404);
            throw new \Exception('User [' . $id . '] not found');
        }
        return $user;
    }

    /**
     * Vérifie l'existence d'une tâche et la retourne.
     * @throws \Exception si la tâche n'existe pas (code 404).
     */
    protected function requireTask(int $id): Task
    {
        $task = new Task($id);
        if (!$task->isLoaded()) {
            $this->setCode(404);
            throw new \Exception('Task [' . $id . '] not found');
        }
        return $task;
    }
}
