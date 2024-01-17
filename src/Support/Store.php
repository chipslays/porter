<?php

namespace Porter\Support;

use Closure;

class Store
{
    protected array $data = [];

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->data[$key] ?? $default;

        return $value instanceof Closure
            ? call_user_func($value)
            : $value;
    }

    /**
     * @param string $key
     * @return self
     */
    public function remove(string $key): self
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }
}