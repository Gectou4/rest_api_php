<?php
namespace G4\Api\Controller;

use G4\Api\Model\TaskStatus;

/** Contrôleur des opérations CRUD sur les tâches et leurs associations aux utilisateurs. */
class Task extends ControllerAbstract
{
    #[\Override]
    public function getIndexAction(): mixed
    {
        $task = new \G4\Api\Model\Task();
        return $task->getAll();
    }

    /** Alias POST → délègue à putAddTaskAction(). */
    public function postAddTaskAction(): mixed
    {
        return $this->putAddTaskAction();
    }

    /**
     * Crée une nouvelle tâche.
     * Retourne 201 + le tableau complet de la tâche en cas de succès.
     * @throws \Exception si le titre est absent.
     */
    public function putAddTaskAction(): mixed
    {
        if (empty($this->getParam('title'))) {
            $this->setCode(400);
            throw new \Exception('Title is required');
        }

        $task   = new \G4\Api\Model\Task();
        $result = $task
            ->setTitle($this->getParam('title'))
            ->setDescription($this->getParam('description', ''))
            ->setStatus((int) $this->getParam('status', TaskStatus::Backlog->value))
            ->save();

        if ($result) {
            $this->setCode(201);
            return $task->toArray();
        }

        $this->setCode(500);
        return 'Unable to create new Task';
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

        $user = new \G4\Api\Model\User((int) $this->getParam('userId'));
        if (!$user->isLoaded()) {
            $this->setCode(400);
            return 'User [' . $this->getParam('userId') . '] not exists';
        }

        $task = new \G4\Api\Model\Task((int) $this->getParam('taskId'));
        if (!$task->isLoaded()) {
            $this->setCode(400);
            return 'Task [' . $this->getParam('taskId') . '] not exists';
        }

        $userTask = new \G4\Api\Model\UserTask((int) $this->getParam('userId'));
        if ($userTask->addTaskId((int) $this->getParam('taskId'))->save()) {
            return 1;
        }

        return 'Unable to add Task to user';
    }

    /**
     * Supprime une tâche après avoir vérifié son existence.
     * Note : ne vérifie pas si des utilisateurs ont encore cette tâche associée.
     */
    public function deleteTaskAction(): mixed
    {
        $this->setCode(500);

        $task = new \G4\Api\Model\Task((int) $this->getParam('id'));
        if (!$task->isLoaded()) {
            $this->setCode(400);
            return 'Task [' . $this->getParam('id') . '] not exists';
        }

        if ($task->delete()) {
            return 1;
        }

        return 'Unable to delete Task';
    }

    /**
     * Retire l'association entre un utilisateur et une tâche.
     * Idempotente : retourne 1 même si la tâche n'était pas dans la liste de l'utilisateur.
     */
    public function deleteUserTaskAction(): mixed
    {
        $this->setCode(500);

        $user = new \G4\Api\Model\User((int) $this->getParam('userId'));
        if (!$user->isLoaded()) {
            $this->setCode(400);
            return 'User [' . $this->getParam('userId') . '] not exists';
        }

        $userTask = new \G4\Api\Model\UserTask((int) $this->getParam('userId'));

        if ($userTask->hasTask((int) $this->getParam('taskId'))) {
            if ($userTask->removeTaskId((int) $this->getParam('taskId'))->save()) {
                return 1;
            }
            return 'Unable to delete Task of user';
        }

        return 1;
    }

    /** Alias POST → délègue à putEditTaskAction(). */
    public function postEditTaskAction(): mixed
    {
        return $this->putEditTaskAction();
    }

    /**
     * Mise à jour partielle d'une tâche : les champs non fournis conservent leur valeur actuelle.
     * @throws \Exception si l'id est absent ou si la tâche est introuvable.
     */
    public function putEditTaskAction(): mixed
    {
        if (empty($this->getParam('id'))) {
            $this->setCode(400);
            throw new \Exception('Id of task to edit is required');
        }

        $task = new \G4\Api\Model\Task((int) $this->getParam('id'));
        if (!$task->isLoaded()) {
            $this->setCode(400);
            throw new \Exception('Task not found');
        }

        $saved = $task
            ->setTitle($this->getParam('title', $task->getTitle()))
            ->setDescription($this->getParam('description', $task->getDescription()))
            ->setStatus($this->getParam('status', $task->getStatus()))
            ->save();

        if ($saved) {
            return 1;
        }

        $this->setCode(500);
        return 'Unable to update Task';
    }
}
