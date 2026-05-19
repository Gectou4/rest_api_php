<?php

declare(strict_types=1);

namespace G4\Api\Model;

/** Entité utilisateur. */
class User extends ModelAbstract
{
    protected string $email = '';

    protected string $name = '';

    protected string $table = 'user';

    /**
     * Hydrate l'objet depuis la table user.
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
        $sth = $this->db->prepare('SELECT email, name FROM `' . $this->table . '` WHERE user_id = ?');
        $sth->execute([$this->getId()]);
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            $this->setName($result['name']);
            $this->setEmail($result['email']);
            $this->loaded = true;
        }
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** Charge et retourne la liste des tâches associées à cet utilisateur. */
    public function getTask(): UserTask
    {
        return UserTask::getTaskByUser($this);
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'user_id' => $this->getId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
        ];
    }
}
