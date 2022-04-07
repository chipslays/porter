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
        $this->saveData();
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
    protected function loadData(): void
    {
        $path = rtrim($this->path, '/\\');

        if (!file_exists($path)) {
            $this->data = new Collection;
        } else {
            $this->data = new Collection(unserialize(file_get_contents($path)));
        }
    }

    /**
     * @return void
     */
    protected function saveData(): void
    {
        $path = rtrim($this->path, '/\\');

        if ($path == '') return;

        file_put_contents($path, serialize($this->data));
    }
}