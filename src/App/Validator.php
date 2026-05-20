<?php

declare(strict_types=1);

namespace G4\Api\App;

/**
 * Validateur léger pour les paramètres d'entrée API.
 *
 * Usage :
 *   $v = new Validator($params);
 *   $v->required('title')->string()->max(255)->end();
 *   $v->optional('status')->int()->in([1, 2, 3, 4, 5])->end();
 *   if ($v->fails()) { return $this->fail($v->firstError(), 400); }
 */
final class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var list<string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function firstError(): string
    {
        return $this->errors[0] ?? 'Validation failed';
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function get(string $key): mixed
    {
        return $this->validated[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->validated;
    }

    /** @internal */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /** @internal */
    public function setValidated(string $key, mixed $value): void
    {
        $this->validated[$key] = $value;
    }

    public function required(string $key): FieldValidator
    {
        if (!array_key_exists($key, $this->data) || $this->data[$key] === '' || $this->data[$key] === null) {
            $this->errors[] = ucfirst($key) . ' is required';
            return new FieldValidator($this, $key, null, true);
        }
        return new FieldValidator($this, $key, $this->data[$key], false);
    }

    public function optional(string $key): FieldValidator
    {
        $value = $this->data[$key] ?? null;
        return new FieldValidator($this, $key, $value, false);
    }
}

// @mago-ignore lint:single-class-per-file
final class FieldValidator
{
    private Validator $validator;
    private string $key;
    private mixed $value;
    private bool $skip;

    public function __construct(Validator $validator, string $key, mixed $value, bool $skip)
    {
        $this->validator = $validator;
        $this->key = $key;
        $this->value = $value;
        $this->skip = $skip;
    }

    public function string(): self
    {
        if ($this->skip || $this->value === null) {
            return $this;
        }
        if (!\is_string($this->value)) {
            $this->validator->addError(ucfirst($this->key) . ' must be a string');
            $this->skip = true;
        }
        return $this;
    }

    public function int(): self
    {
        if ($this->skip || $this->value === null) {
            return $this;
        }
        if (\is_int($this->value)) {
            return $this;
        }
        if (\is_string($this->value) && ctype_digit($this->value)) {
            $this->value = (int) $this->value;
            return $this;
        }
        $this->validator->addError(ucfirst($this->key) . ' must be an integer');
        $this->skip = true;
        return $this;
    }

    public function max(int $max): self
    {
        if ($this->skip || $this->value === null) {
            return $this;
        }
        if (\is_string($this->value) && strlen($this->value) > $max) {
            $this->validator->addError(ucfirst($this->key) . ' must not exceed ' . $max . ' characters');
            $this->skip = true;
        }
        return $this;
    }

    public function in(array $allowed): self
    {
        if ($this->skip || $this->value === null) {
            return $this;
        }
        if (!in_array($this->value, $allowed, true)) {
            $this->validator->addError(ucfirst($this->key) . ' must be one of: ' . implode(', ', array_map('strval', $allowed)));
            $this->skip = true;
        }
        return $this;
    }

    public function end(): Validator
    {
        if (!$this->skip) {
            $this->validator->setValidated($this->key, $this->value);
        }
        return $this->validator;
    }
}
