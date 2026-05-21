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

    /**
     * Cherche un utilisateur par son api_token et retourne son ID, ou null si introuvable.
     */
    public static function loadByToken(#[\SensitiveParameter] string $token): ?int
    {
        $db = \G4\Api\App\DB::getInstance('master')->getDB();
        \assert($db instanceof \PDO, 'Database connection must be established');
        $sth = $db->prepare('SELECT user_id FROM `user` WHERE api_token = ?');
        \assert($sth instanceof \PDOStatement, 'PDO prepared statement must be valid');
        $sth->execute([$token]);
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        \assert(\is_array($row), 'PDO fetch with FETCH_ASSOC must return an array');
        return (int) $row['user_id'];
    }

    /** Retourne tous les utilisateurs. */
    public static function loadAll(): array
    {
        $db = \G4\Api\App\DB::getInstance('master')->getDB();
        $sth = $db->query('SELECT user_id, name, email FROM `user` ORDER BY user_id');
        $users = [];
        foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $user = new self();
            $user->setId((int) $row['user_id']);
            $user->setName($row['name']);
            $user->setEmail($row['email']);
            $user->loaded = true;
            $users[] = $user;
        }
        return $users;
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
