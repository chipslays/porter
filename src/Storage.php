<?php

namespace Porter;

use Chipslays\Collection\Collection;

class Storage
{
    public Collection $data;

    /**
     * Constructor.
     *
     * If you specify an incorrect path, the data will be
     * stored in RAM, and the data will be lost upon restart.
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
     * @return bool
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
        $path = $this->getPath();

        if (!$path) {
            return;
        }

        file_put_contents($path, serialize($this->data));
    }

    /**
     * Remove storage file from disk.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $path = $this->getPath();

        if (!$path) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Returns `string` if path not empty.
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        $path = rtrim($this->path, '/\\');

        return $path == '' ? null : $path;
    }
}