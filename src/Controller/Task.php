<?php

declare(strict_types=1);

namespace G4\Api\Controller;

use G4\Api\Model\TaskStatus;

/** Contrôleur CRUD pour les tâches. */
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
     */
    public function putAddTaskAction(): mixed
    {
        $title = $this->getParam('title');
        if ($title === '' || $title === null) {
            $this->setCode(400);
            throw new \Exception('Title is required');
        }

        $description = $this->getParam('description');
        $status = $this->getParam('status');

        $task = new \G4\Api\Model\Task();
        $result = $task
            ->setTitle((string) $title)
            ->setDescription(\is_string($description) ? $description : '')
            ->setStatus(\is_int($status) ? $status : TaskStatus::Backlog->value)
            ->save();

        if ($result) {
            $this->setCode(201);
            return $task->toArray();
        }

        $this->setCode(500);
        return 'Unable to create new Task';
    }

    /**
     * Mise à jour partielle d'une tâche : les champs non fournis conservent leur valeur actuelle.
     */
    public function postEditTaskAction(): mixed
    {
        return $this->putEditTaskAction();
    }

    public function putEditTaskAction(): mixed
    {
        $id = $this->getParam('id');
        if ($id === '' || $id === null) {
            $this->setCode(400);
            throw new \Exception('Id of task to edit is required');
        }

        $task = new \G4\Api\Model\Task((int) $id);
        if (!$task->isLoaded()) {
            $this->setCode(400);
            throw new \Exception('Task not found');
        }

        $title = $this->getParam('title');
        $description = $this->getParam('description');
        $status = $this->getParam('status');

        $saved = $task
            ->setTitle(\is_string($title) ? $title : $task->getTitle())
            ->setDescription(\is_string($description) ? $description : $task->getDescription())
            ->setStatus(\is_int($status) ? $status : $task->getStatus())
            ->save();

        if ($saved) {
            return 1;
        }

        $this->setCode(500);
        return 'Unable to update Task';
    }

    /**
     * Supprime une tâche après avoir vérifié son existence.
     */
    public function deleteTaskAction(): mixed
    {
        $this->setCode(500);

        $id = (int) $this->getParam('id');

        $task = new \G4\Api\Model\Task($id);
        if (!$task->isLoaded()) {
            $this->setCode(400);
            return 'Task [' . $id . '] not exists';
        }

        if ($task->delete()) {
            return 1;
        }

        return 'Unable to delete Task';
    }
}
