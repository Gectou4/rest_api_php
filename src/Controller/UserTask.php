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
     */
    public function putAddTaskToUserAction(): mixed
    {
        $userId = (int) $this->getParam('userId');
        $taskId = (int) $this->getParam('taskId');

        $this->requireUser($userId);
        $this->requireTask($taskId);

        $userTask = new \G4\Api\Model\UserTask($userId);
        if ($userTask->addTask($taskId)->save()) {
            return 1;
        }

        $this->setCode(500);
        return 'Unable to add Task to user';
    }

    /**
     * Retire l'association entre un utilisateur et une tâche.
     * Idempotente : retourne 1 même si la tâche n'était pas dans la liste de l'utilisateur.
     */
    public function deleteUserTaskAction(): mixed
    {
        $userId = (int) $this->getParam('userId');
        $taskId = (int) $this->getParam('taskId');

        $this->requireUser($userId);

        $userTask = new \G4\Api\Model\UserTask($userId);

        if ($userTask->hasTask($taskId)) {
            if ($userTask->removeTask($taskId)->save()) {
                return 1;
            }

            $this->setCode(500);
            return 'Unable to delete Task of user';
        }

        return 1;
    }
}
