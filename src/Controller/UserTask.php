<?php

declare(strict_types=1);

namespace G4\Api\Controller;

/** Contrôleur pour les associations utilisateur-tâche. */
class UserTask extends ControllerAbstract
{
    #[\Override]
    public function getIndexAction(): mixed
    {
        return $this->ok([]);
    }

    /** Alias POST → délègue à putAddTaskToUserAction(). */
    public function postAddTaskToUserAction(): mixed
    {
        return $this->putAddTaskToUserAction();
    }

    /** Associe une tâche existante à un utilisateur existant. */
    public function putAddTaskToUserAction(): mixed
    {
        $userId = (int) $this->getParam('userId');
        $taskId = (int) $this->getParam('taskId');

        $this->requireUser($userId);
        $this->requireTask($taskId);

        $userTask = new \G4\Api\Model\UserTask($userId);
        if ($userTask->addTask($taskId)->save()) {
            return $this->ok(1);
        }

        return $this->fail('Unable to add Task to user', 500);
    }

    /**
     * Retire l'association entre un utilisateur et une tâche.
     * Idempotente : retourne 1 même si la tâche n'était pas dans la liste.
     */
    public function deleteUserTaskAction(): mixed
    {
        $userId = (int) $this->getParam('userId');
        $taskId = (int) $this->getParam('taskId');

        $this->requireUser($userId);

        $userTask = new \G4\Api\Model\UserTask($userId);

        if ($userTask->hasTask($taskId)) {
            if ($userTask->removeTask($taskId)->save()) {
                return $this->ok(1);
            }

            return $this->fail('Unable to delete Task of user', 500);
        }

        return $this->ok(1);
    }
}
