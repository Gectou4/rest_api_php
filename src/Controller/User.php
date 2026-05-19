<?php

declare(strict_types=1);

namespace G4\Api\Controller;

/** Contrôleur des opérations de lecture sur les utilisateurs et leurs tâches. */
class User extends ControllerAbstract
{
    /**
     * Retourne les données d'un utilisateur par son id.
     * Répond 404 si l'utilisateur est introuvable.
     */
    #[\Override]
    public function getIndexAction(): mixed
    {
        $user = new \G4\Api\Model\User((int) $this->getParam('id'));
        if (!$user->isLoaded()) {
            $this->setCode(404);
            return 'No user found';
        }

        return $user->toArray();
    }

    /**
     * Retourne la liste des tâches associées à un utilisateur.
     * Répond 404 si l'utilisateur est introuvable.
     */
    public function getUserTaskAction(): mixed
    {
        $user = new \G4\Api\Model\User((int) $this->getParam('id'));
        if (!$user->isLoaded()) {
            $this->setCode(404);
            return 'No user found';
        }

        return $user->getTask()->toArray();
    }
}
