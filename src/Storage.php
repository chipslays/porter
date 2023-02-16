<?php

namespace Porter;

use Porter\Support\Collection;

class Storage
{
    /**
     * Path to file.
     *
     * @var string
     */
    protected string $filename;

    /**
     * Collection of data.
     *
     * @var Collection
     */
    public Collection $data;

    public function __construct(string $filename = null)
    {
        if (!$filename) {
            $filename = rtrim(sys_get_temp_dir(), '/\\') . '/porter/server_storage.php';
        }

        $this->load($filename);
    }

    public function load(string $filename): self
    {
        $storageDir = dirname($filename);

        if (!file_exists($storageDir)) {
            mkdir($storageDir, recursive: true);
        }

        if (!file_exists($filename)) {
            file_put_contents($filename, serialize([]));
        }

        $this->filename = $filename;

        $this->data = new Collection(unserialize(file_get_contents($this->filename)));

        return $this;
    }

    public function save(): self
    {
        file_put_contents($this->filename, serialize($this->data->toArray()));

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function put(string $key, mixed $value): self
    {
        $this->data->set($key, $value);

        return $this;
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
     * Remove value from storage.
     *
     * @param string ...$keys
     * @return self
     */
    public function remove(string ...$keys): self
    {
        $this->data->remove(...$keys);

        return $this;
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
     * @return string
     */
    public function filename(): string
    {
        return $this->filename;
    }
}