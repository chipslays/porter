<?php

namespace Porter;

use Chipslays\Collection\Collection;

class Storage
{
    public Collection $data;

    /**
     * Constructor.
     *
     * @param string $path
     */
    public function __construct(public string $path = '')
    {
        $this->path = rtrim($this->path, '/\\');
        $this->loadData();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, mixed $value): void
    {
        $this->data->set($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    /**
     * @return void
     */
    public function loadData(): void
    {
        if (!file_exists($this->path)) {
            $this->data = new Collection;
        } else {
            $this->data = new Collection(unserialize(file_get_contents($this->path)));
        }
    }

    /**
     * @return void
     */
    public function saveData(): void
    {
        file_put_contents($this->path, serialize($this->data));
    }
}