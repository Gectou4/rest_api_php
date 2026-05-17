<?php

declare(strict_types=1);

namespace G4\Api\Model;

/**
 * Agrégat représentant la relation N:N entre un utilisateur et ses tâches.
 * Correspond à la table pivot user_task.
 */
class UserTask extends ModelAbstract
{
    protected int    $userId   = 0;
    protected array  $taskList = [];
    protected string $table    = 'user_task';

    /** Délègue le chargement à loadByUserId(). */
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
        return $this->getUserId();
    }

    public function setId(int $id): void
    {
        $this->setUserId($id);
    }

    public function getTaskIds(): array
    {
        return array_keys($this->taskList);
    }

    public function getTaskList(): array
    {
        return $this->taskList;
    }

    /** Ajoute une tâche à la liste en mémoire (instancie l'objet Task correspondant). */
    public function addTaskId(int $taskId): static
    {
        $this->taskList[$taskId] = new Task($taskId);
        return $this;
    }

    /** Ajoute un objet Task directement (recharge depuis la DB pour garantir les données). */
    public function addTask(Task $task): static
    {
        $this->taskList[$task->getId()] = new Task($task->getId());
        return $this;
    }

    public function removeTask(Task $task): static
    {
        unset($this->taskList[$task->getId()]);
        return $this;
    }

    public function removeTaskId(int $taskId): static
    {
        unset($this->taskList[$taskId]);
        return $this;
    }

    /** Factory : crée une instance UserTask et la charge depuis l'objet User fourni. */
    public static function getTaskByUser(User $user): static
    {
        $instance = new static();
        $instance->loadByUser($user);
        return $instance;
    }

    /** Indique si la tâche donnée est présente dans la liste en mémoire. */
    public function hasTask(int $taskId): bool
    {
        return array_key_exists($taskId, $this->taskList);
    }

    /**
     * Stratégie de remplacement complet dans une transaction :
     * supprime toutes les associations existantes pour l'utilisateur,
     * puis insère les tâches de la liste courante.
     */
    #[\Override]
    public function save(): bool
    {
        \assert($this->db !== null);
        try {
            $this->db->beginTransaction();

            $sth = $this->db->prepare('DELETE FROM `user_task` WHERE user_id = ?');
            $sth->execute([$this->getUserId()]);

            $sth = $this->db->prepare('INSERT INTO `user_task` (`user_id`, `task_id`) VALUES (?, ?)');
            foreach (array_keys($this->taskList) as $taskId) {
                $sth->execute([$this->getUserId(), $taskId]);
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
        \assert($this->db !== null);
        try {
            $this->db->beginTransaction();

            $sth = $this->db->prepare('DELETE FROM `user_task` WHERE user_id = ? AND task_id = ?');
            $sth->execute([$this->getUserId(), $taskId]);

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
        return ['user_id' => $this->getUserId(), 'tasks' => $tasks];
    }

    protected function loadByUser(User $user): void
    {
        $this->loadByUserId($user->getId());
    }

    /**
     * Charge toutes les task_id associées à l'utilisateur et instancie les objets Task.
     * Idempotente grâce au guard $loaded.
     */
    protected function loadByUserId(int $userId): void
    {
        if ($this->loaded) {
            return;
        }
        $this->setId($userId);
        $sth = $this->db->prepare('SELECT task_id FROM user_task WHERE user_id = ?');
        $sth->execute([$this->getUserId()]);
        foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $this->addTaskId((int) $row['task_id']);
        }
        $this->loaded = true;
    }
}
