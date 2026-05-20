<?php

declare(strict_types=1);

namespace G4\Api\Controller;

/** Contrôleur des opérations de lecture sur les utilisateurs et leurs tâches. */
class User extends ControllerAbstract
{
    #[\Override]
    public function getIndexAction(): mixed
    {
        return $this->ok($this->requireUser((int) $this->getParam('id'))->toArray());
    }

    /** Retourne la liste des tâches associées à un utilisateur. */
    public function getUserTaskAction(): mixed
    {
        return $this->ok($this->requireUser((int) $this->getParam('id'))->getTask()->toArray());
    }
}
