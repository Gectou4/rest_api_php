<?php

declare(strict_types=1);

namespace G4\Api\Controller;

use G4\Api\App\Validator;
use G4\Api\Model\User as UserModel;

/** Contrôleur des opérations de lecture sur les utilisateurs et leurs tâches. */
class User extends ControllerAbstract
{
    /** Retourne la liste de tous les utilisateurs. */
    public function getListAction(): mixed
    {
        return array_map(static fn(UserModel $u) => $u->toArray(), UserModel::loadAll());
    }

    #[\Override]
    public function getIndexAction(): mixed
    {
        $v = new Validator($this->getParams());
        $v->required('id')->int()->end();

        if ($v->fails()) {
            return $this->fail($v->firstError(), 400);
        }

        return $this->ok($this->requireUser((int) $v->get('id'))->toArray());
    }

    /** Retourne la liste des tâches associées à un utilisateur. */
    public function getUserTaskAction(): mixed
    {
        $v = new Validator($this->getParams());
        $v->required('id')->int()->end();

        if ($v->fails()) {
            return $this->fail($v->firstError(), 400);
        }

        return $this->ok($this->requireUser((int) $v->get('id'))->getTask()->toArray());
    }
}
