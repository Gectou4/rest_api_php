<?php

declare(strict_types=1);

namespace G4\Api\Controller;

use G4\Api\App\Validator;
use G4\Api\Model\TaskStatus;

/** Contrôleur CRUD pour les tâches. */
class Task extends ControllerAbstract
{
    #[\Override]
    public function getIndexAction(): mixed
    {
        return $this->ok((new \G4\Api\Model\Task())->getAll());
    }

    public function postAddTaskAction(): mixed
    {
        return $this->putAddTaskAction();
    }

    /** Crée une nouvelle tâche. Retourne 201 + la tâche créée. */
    public function putAddTaskAction(): mixed
    {
        $v = new Validator($this->getParams());
        $v->required('title')->string()->max(255)->end();
        $v->optional('description')->string()->max(1000)->end();
        $v->optional('status')->int()->in([1, 2, 3, 4, 5])->end();

        if ($v->fails()) {
            return $this->fail($v->firstError(), 400);
        }

        $title = (string) $v->get('title');
        $description = (string) ($v->get('description') ?? '');
        $status = (int) ($v->get('status') ?? TaskStatus::Backlog->value);

        $task = new \G4\Api\Model\Task();
        $result = $task
            ->setTitle($title)
            ->setDescription($description)
            ->setStatus($status)
            ->save();

        if ($result) {
            $this->setCode(201);
            return $this->ok($task->toArray());
        }

        return $this->fail('Unable to create new Task', 500);
    }

    public function postEditTaskAction(): mixed
    {
        return $this->putEditTaskAction();
    }

    /** Mise à jour partielle d'une tâche. */
    public function putEditTaskAction(): mixed
    {
        $v = new Validator($this->getParams());
        $v->required('id')->int()->end();
        $v->optional('title')->string()->max(255)->end();
        $v->optional('description')->string()->max(1000)->end();
        $v->optional('status')->int()->in([1, 2, 3, 4, 5])->end();

        if ($v->fails()) {
            return $this->fail($v->firstError(), 400);
        }

        $task = $this->requireTask((int) $v->get('id'));

        $title = $v->get('title');
        $description = $v->get('description');
        $status = $v->get('status');

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
        $v = new Validator($this->getParams());
        $v->required('id')->int()->end();

        if ($v->fails()) {
            return $this->fail($v->firstError(), 400);
        }

        $id = (int) $v->get('id');
        $this->requireTask($id);

        $task = new \G4\Api\Model\Task($id);
        if ($task->delete()) {
            return $this->ok(1);
        }

        return $this->fail('Unable to delete Task', 500);
    }
}
