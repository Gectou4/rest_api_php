<?php

declare(strict_types=1);

namespace G4\Api\Controller;

/** Contrôleur pour les associations utilisateur-tâche. */
class UserTask extends ControllerAbstract
{
    #[\Override]
    public function getIndexAction(): mixed
    {
        return [];
    }

    /** Alias POST → délègue à putAddTaskToUserAction(). */
    public function postAddTaskToUserAction(): mixed
    {
        return $this->putAddTaskToUserAction();
    }

    /**
     * Associe une tâche existante à un utilisateur existant.
     * Valide l'existence des deux entités avant de créer le lien.
     */
    public function putAddTaskToUserAction(): mixed
    {
        $this->setCode(500);

        $userId = (int) $this->getParam('userId');
        $taskId = (int) $this->getParam('taskId');

        $user = new \G4\Api\Model\User($userId);
        if (!$user->isLoaded()) {
            $this->setCode(400);
            return 'User [' . $userId . '] not exists';
        }

        $task = new \G4\Api\Model\Task($taskId);
        if (!$task->isLoaded()) {
            $this->setCode(400);
            return 'Task [' . $taskId . '] not exists';
        }

        $userTask = new \G4\Api\Model\UserTask($userId);
        if ($userTask->addTask($taskId)->save()) {
            return 1;
        }

        return 'Unable to add Task to user';
    }

    /**
     * Retire l'association entre un utilisateur et une tâche.
     * Idempotente : retourne 1 même si la tâche n'était pas dans la liste de l'utilisateur.
     */
    public function deleteUserTaskAction(): mixed
    {
        $this->setCode(500);

        $userId = (int) $this->getParam('userId');
        $taskId = (int) $this->getParam('taskId');

        $user = new \G4\Api\Model\User($userId);
        if (!$user->isLoaded()) {
            $this->setCode(400);
            return 'User [' . $userId . '] not exists';
        }

        $userTask = new \G4\Api\Model\UserTask($userId);

        if ($userTask->hasTask($taskId)) {
            if ($userTask->removeTask($taskId)->save()) {
                return 1;
            }

            return 'Unable to delete Task of user';
        }

        return 1;
    }
}
