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
        return $this->requireUser((int) $this->getParam('id'))->toArray();
    }

    /**
     * Retourne la liste des tâches associées à un utilisateur.
     */
    public function getUserTaskAction(): mixed
    {
        return $this->requireUser((int) $this->getParam('id'))->getTask()->toArray();
    }
}
