<?php

declare(strict_types=1);

namespace G4\Api\Model;

/**
 * Entité représentant la relation N:N entre un utilisateur et ses tâches.
 * Correspond à la table pivot user_task.
 */
class UserTask extends ModelAbstract
{
    protected int    $userId   = 0;
    protected array  $taskList = [];
    protected string $table    = 'user_task';

    #[\Override]
    public function load(int $id): void
    {
        if (!$this->loaded) {
            $this->loadByUserId($id);
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $id): static
    {
        $this->userId = $id;
        return $this;
    }

    #[\Override]
    public function getId(): int
    {
        return $this->userId;
    }

    #[\Override]
    public function setId(int $id): static
    {
        $this->userId = $id;
        return $this;
    }

    public function getTaskIds(): array
    {
        return array_keys($this->taskList);
    }

    public function getTaskList(): array
    {
        return $this->taskList;
    }

    /** Ajoute une tâche à la liste en mémoire. Accepte un ID ou un objet Task. */
    public function addTask(int|Task $task): static
    {
        $taskId = $task instanceof Task ? $task->getId() : $task;
        $this->taskList[$taskId] = new Task($taskId);
        return $this;
    }

    /** Retire une tâche de la liste en mémoire. Accepte un ID ou un objet Task. */
    public function removeTask(int|Task $task): static
    {
        $taskId = $task instanceof Task ? $task->getId() : $task;
        unset($this->taskList[$taskId]);
        return $this;
    }

    /** Factory : crée et charge un UserTask depuis un objet User. */
    public static function getTaskByUser(User $user): static
    {
        $instance = new static();
        $instance->loadByUserId($user->getId());
        return $instance;
    }

    /** Indique si la tâche donnée est présente dans la liste en mémoire. */
    public function hasTask(int $taskId): bool
    {
        return array_key_exists($taskId, $this->taskList);
    }

    /**
     * Remplace toutes les associations dans une transaction :
     * supprime puis réinsère les tâches de la liste courante.
     */
    public function save(): bool
    {
        \assert($this->db instanceof \PDO, 'Database connection must be established before saving');
        try {
            $this->db->beginTransaction();

            $sth = $this->db->prepare('DELETE FROM `user_task` WHERE user_id = ?');
            $sth->execute([$this->userId]);

            $sth = $this->db->prepare('INSERT INTO `user_task` (`user_id`, `task_id`) VALUES (?, ?)');
            foreach (array_keys($this->taskList) as $taskId) {
                $sth->execute([$this->userId, $taskId]);
            }

            $sth->closeCursor();
            return $this->db->commit();

        } catch (\Exception) {
            $this->db->rollBack();
            return false;
        }
    }

    /** Supprime une seule association user-task dans une transaction atomique. */
    public function deleteUserTask(int $taskId): bool
    {
        \assert($this->db instanceof \PDO, 'Database connection must be established before deleting association');
        try {
            $this->db->beginTransaction();

            $sth = $this->db->prepare('DELETE FROM `user_task` WHERE user_id = ? AND task_id = ?');
            $sth->execute([$this->userId, $taskId]);

            return $this->db->commit();

        } catch (\Exception) {
            $this->db->rollBack();
            return false;
        }
    }

    #[\Override]
    public function toArray(): array
    {
        $tasks = [];
        foreach ($this->taskList as $taskId => $task) {
            $tasks[$taskId] = $task->toArray();
        }
        return ['user_id' => $this->userId, 'tasks' => $tasks];
    }

    /** Charge toutes les tâches associées à l'utilisateur en une seule requête JOIN. */
    protected function loadByUserId(int $userId): void
    {
        if ($this->loaded) {
            return;
        }

        $this->userId = $userId;
        $sth = $this->db->prepare('
            SELECT t.task_id, t.status, t.title, t.description, t.creation_date
            FROM user_task ut
            JOIN task t ON ut.task_id = t.task_id
            WHERE ut.user_id = ?
        ');
        $sth->execute([$this->userId]);
        foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $task = new Task();
            $task->setId((int) $row['task_id'])
                ->setStatus((int) $row['status'])
                ->setTitle($row['title'])
                ->setDescription($row['description'])
                ->setCreationDate($row['creation_date']);
            $this->taskList[$task->getId()] = $task;
        }

        $this->loaded = true;
    }
}
