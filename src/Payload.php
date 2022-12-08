<?php

namespace Porter;

use Porter\Support\Collection;

class Payload
{
    public readonly string $type;

    public Collection $data;

    /**
     * Constructor.
     *
     * @param array $payload
     */
    public function __construct(public array $payload)
    {
        $this->type = $payload['type'];

        if (isset($payload['data']) && is_array($payload['data'])) {
            $this->data = new Collection($this->emptyStringsToNull($payload['data']));
        } else {
            $this->data = new Collection;
        }
    }

    protected function emptyStringsToNull(array $data): array
    {
        array_walk_recursive($data, function(&$value) {
            $value = trim($value) === '' ? null : $value;
        });

        return $data;
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
     * Has value in data.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    /**
     * Validate payload data.
     *
     * @see https://respect-validation.readthedocs.io/en/latest/ Documentation & Examples
     *
     * @param string|array $rule
     * @param string $key Payload key
     * @return bool
     */
    public function is(string|array $rule, string $key): bool
    {
        $rule = (array) $rule;
        return Server::getInstance()->validator()::create()
            ->__call($rule[0], isset($rule[1]) ? (array) $rule[1] : [])
            ->validate($this->data->get($key));
    }
}