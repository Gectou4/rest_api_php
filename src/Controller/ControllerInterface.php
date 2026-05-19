<?php

declare(strict_types=1);

namespace G4\Api\Controller;

interface ControllerInterface
{
    public function getIndexAction(): mixed;

    public function setParams(array $params): void;

    public function getParams(): array;

    public function getParam(string $key, mixed $default = null): mixed;

    public function getCode(): int;

    public function setCode(int $code): void;
}
