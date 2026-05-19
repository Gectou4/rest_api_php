<?php

declare(strict_types=1);

namespace G4\Api\Model;

/**
 * Entité tâche. Le statut est géré via l'enum TaskStatus.
 * Les constantes STATUS_* sont conservées comme alias entiers pour la compatibilité.
 */
class Task extends ModelAbstract
{
    // Aliases de compatibilité — préférer TaskStatus::* dans le nouveau code
    const int STATUS_BACKLOG     = TaskStatus::Backlog->value;

    const int STATUS_TODO        = TaskStatus::Todo->value;

    const int STATUS_IN_PROGRESS = TaskStatus::InProgress->value;

    const int STATUS_DONE        = TaskStatus::Done->value;

    const int STATUS_CLOSE       = TaskStatus::Closed->value;

    protected int                 $id           = 0;

    protected string              $title        = '';

    protected string              $description  = '';

    protected ?\DateTimeImmutable $creationDate = null;

    protected TaskStatus          $status       = TaskStatus::Backlog;

    protected string              $table        = 'task';

    #[\Override]
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    #[\Override]
    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hydrate l'objet depuis la table task.
     * $loaded est positionné à true uniquement si une ligne est trouvée,
     * garantissant ainsi la fiabilité de isLoaded() dans les contrôleurs.
     */
    #[\Override]
    public function load(int $id): void
    {
        if ($this->loaded) {
            return;
        }

        $this->setId($id);
        $sth = $this->db->prepare(
            'SELECT status, title, description, creation_date FROM `' . $this->table . '` WHERE task_id = ?'
        );
        $sth->execute([$this->getId()]);
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            $this->setStatus((int) $result['status']);
            $this->setTitle($result['title']);
            $this->setDescription($result['description']);
            $this->setCreationDate($result['creation_date']);
            $this->loaded = true;
        }
    }

    /**
     * Accepte un TaskStatus, un int ou une string (entrée HTTP).
     * Convertit systématiquement vers TaskStatus via TaskStatus::from().
     * @throws \ValueError si la valeur entière n'est pas un cas de l'enum.
     */
    public function setStatus(TaskStatus|int|string $status): static
    {
        $this->status = $status instanceof TaskStatus
            ? $status
            : TaskStatus::from((int) $status);
        return $this;
    }

    public function setDescription(string $desc): static
    {
        $this->description = $desc;
        return $this;
    }

    /**
     * Parse une date au format 'Y-m-d H:i:s' en DateTimeImmutable (fuseau Europe/Paris).
     * En cas de format invalide, $creationDate reste null et getCreationDate() retournera l'heure courante.
     */
    public function setCreationDate(string $datetime): static
    {
        $this->creationDate = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $datetime,
            new \DateTimeZone('Europe/Paris')
        ) ?? null;
        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    /** Retourne la date de création, ou l'instant présent si elle n'a pas encore été définie. */
    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creationDate ?? new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Persiste la tâche en base : INSERT si id=0, UPDATE sinon.
     * Après un INSERT réussi, l'id est mis à jour avec lastInsertId().
     */
    public function save(): bool
    {
        try {
            if ($this->getId() <= 0) {
                $sth = $this->db->prepare(
                    'INSERT INTO `' . $this->table . '` (status, title, description, creation_date)
                     VALUES (:status, :title, :description, :creation_date)'
                );

                $sth->bindValue(':status',        $this->getStatus()->value,                       \PDO::PARAM_INT);
                $sth->bindValue(':title',         $this->getTitle(),                               \PDO::PARAM_STR);
                $sth->bindValue(':description',   $this->getDescription(),                         \PDO::PARAM_STR);
                $sth->bindValue(':creation_date', $this->getCreationDate()->format('Y-m-d H:i:s'), \PDO::PARAM_STR);

                $saved = $sth->execute();

                if ($saved && $this->getId() <= 0) {
                    $this->setId((int) $this->db->lastInsertId());
                }

                return $saved;
            }

            $sth = $this->db->prepare(
                'UPDATE `' . $this->table . '` SET status=:status, title=:title,
                 description=:description, creation_date=:creation_date
                 WHERE task_id = :id'
            );
            $sth->bindValue(':id', $this->getId(), \PDO::PARAM_INT);

            $sth->bindValue(':status',        $this->getStatus()->value,                       \PDO::PARAM_INT);
            $sth->bindValue(':title',         $this->getTitle(),                               \PDO::PARAM_STR);
            $sth->bindValue(':description',   $this->getDescription(),                         \PDO::PARAM_STR);
            $sth->bindValue(':creation_date', $this->getCreationDate()->format('Y-m-d H:i:s'), \PDO::PARAM_STR);

            return $sth->execute();

        } catch (\Exception) {
            return false;
        }
    }

    /** Supprime la tâche de la base via une requête préparée. */
    public function delete(): bool
    {
        try {
            $sth = $this->db->prepare('DELETE FROM `' . $this->table . '` WHERE task_id = :id');
            $sth->bindValue(':id', $this->getId(), \PDO::PARAM_INT);
            return (bool) $sth->execute();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Retourne toutes les tâches sous forme de tableaux associatifs bruts (non hydratés).
     * TODO(@gectou4) : implémenter $offset et $limit pour la pagination.
     */
    public function getAll(?int $offset = null, ?int $limit = null): array
    {
        $taskList = [];
        try {
            foreach ($this->db->query(
                'SELECT task_id, status, title, description, creation_date FROM `' . $this->table . '`'
            ) as $row) {
                $status = TaskStatus::tryFrom((int) $row['status']);
                $taskList[$row['task_id']] = [
                    'task_id'       => (int) $row['task_id'],
                    'status'        => $status->value ?? (int) $row['status'],
                    'title'         => $row['title'],
                    'description'   => $row['description'],
                    'creation_date' => $row['creation_date'],
                ];
            }
        } catch (\Exception) {
            // @mago-expect lint:no-empty-catch-clause
        }

        return $taskList;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'task_id'       => $this->getId(),
            'status'        => $this->getStatus()->value,
            'title'         => $this->getTitle(),
            'description'   => $this->getDescription(),
            'creation_date' => $this->getCreationDate()->format('Y-m-d H:i:s'),
        ];
    }
}
