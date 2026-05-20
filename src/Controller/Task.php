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
        return $this->ok((new \G4\Api\Model\Task())->getAll());
    }

    /** Alias POST → délègue à putAddTaskAction(). */
    public function postAddTaskAction(): mixed
    {
        return $this->putAddTaskAction();
    }

    /** Crée une nouvelle tâche. Retourne 201 + la tâche créée. */
    public function putAddTaskAction(): mixed
    {
        $title = $this->getParam('title');
        if ($title === '' || $title === null) {
            return $this->fail('Title is required', 400);
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
            return $this->ok($task->toArray());
        }

        return $this->fail('Unable to create new Task', 500);
    }

    /** Alias POST → délègue à putEditTaskAction(). */
    public function postEditTaskAction(): mixed
    {
        return $this->putEditTaskAction();
    }

    /** Mise à jour partielle d'une tâche. */
    public function putEditTaskAction(): mixed
    {
        $id = $this->getParam('id');
        if ($id === '' || $id === null) {
            return $this->fail('Id of task to edit is required', 400);
        }

        $task = $this->requireTask((int) $id);

        $title = $this->getParam('title');
        $description = $this->getParam('description');
        $status = $this->getParam('status');

        $saved = $task
            ->setTitle(\is_string($title) ? $title : $task->getTitle())
            ->setDescription(\is_string($description) ? $description : $task->getDescription())
            ->setStatus(\is_int($status) ? $status : $task->getStatus())
            ->save();

        if ($saved) {
            return $this->ok(1);
        }

        return $this->fail('Unable to update Task', 500);
    }

    /** Supprime une tâche après avoir vérifié son existence. */
    public function deleteTaskAction(): mixed
    {
        $id = (int) $this->getParam('id');
        $this->requireTask($id);

        $task = new \G4\Api\Model\Task($id);
        if ($task->delete()) {
            return $this->ok(1);
        }

        return $this->fail('Unable to delete Task', 500);
    }
}
