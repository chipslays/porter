<?php

namespace Porter;

use Chipslays\Collection\Collection;
use Respect\Validation\Validator;

class Payload
{
    public readonly string $type;

    public readonly int $timestamp;

    public Collection $data;

    /**
     * Constructor.
     *
     * @param array $payload
     */
    public function __construct(public array $payload)
    {
        $this->type = $payload['type'];
        $this->data = new Collection($payload['data'] ?? []);
        $this->timestamp = $payload['timestamp'] ?? time();
    }

    /**
     * Get value from data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    /**
     * Validate payload data.
     *
     * @see https://respect-validation.readthedocs.io/en/latest/ Documentation & Examples
     *
     * @param string $method
     * @param string $arguments
     * @return bool
     */
    public function is(string|array $rule, string $property): bool
    {
        $rule = (array) $rule;
        return Server::getInstance()->validator::create()
            ->__call($rule[0], isset($rule[1]) ? (array) $rule[1] : [])
            ->validate($this->data->get($property));
    }
}