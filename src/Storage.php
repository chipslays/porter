<?php

namespace Porter;

use Porter\Exceptions\StorageException;
use Porter\Support\Collection;

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
    public function __construct(protected ?string $path = null)
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
     * Remove value from storage.
     *
     * @param string ...$keys
     * @return self
     */
    public function remove(string ...$keys): self
    {
        $this->data->remove($keys);

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
     * @return void
     */
    protected function loadData(): void
    {
        if (!$this->path) {
            $this->data = new Collection;
            return;
        }

        $path = rtrim($this->path, '/\\');

        if (!file_exists($path)) {
            $this->data = new Collection;
            $this->saveData();
        }

        $this->data = new Collection(unserialize(file_get_contents($path)));
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
    public function deleteLocalFile(): bool
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
        if (!$this->path) {
            return null;
        }

        $path = rtrim($this->path, '/\\');

        return $path;
    }

    /**
     * Set storage path.
     *
     * Pass null for storage in RAM.
     *
     * @param string|null $path
     * @return self
     */
    public function setPath(?string $path = null): self
    {
        $this->path = $path;
        $this->loadData();

        return $this;
    }
}