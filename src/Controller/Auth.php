<?php

declare(strict_types=1);

namespace G4\Api\Controller;

use G4\Api\App\Validator;
use G4\Api\Model\User;

/** Contrôleur d'authentification. */
class Auth extends ControllerAbstract
{
    #[\Override]
    public function getIndexAction(): mixed
    {
        return $this->fail('Method not allowed', 405);
    }

    public function postIndexAction(): mixed
    {
        $v = new Validator($this->getParams());
        $v->required('name')->string()->max(32)->end();
        $v->required('api_token')->string()->max(64)->end();

        if ($v->fails()) {
            return $this->fail($v->firstError(), 400);
        }

        $name = (string) $v->get('name');
        $token = (string) $v->get('api_token');

        $userId = User::loadByToken($token);

        $user = new User((int) ($userId ?? 0));
        if (!$user->isLoaded() || $user->getName() !== $name) {
            return $this->fail('Invalid credentials', 401);
        }

        return $this->ok($user->toArray());
    }
}
