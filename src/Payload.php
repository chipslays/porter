<?php

namespace Porter;

use Chipslays\Collection\Collection;

class Payload
{
    public string $eventId;

    public Collection $data;

    public int $timestamp;

    /**
     * Constructor.
     *
     * @param array $payload
     */
    public function __construct(protected array $payload)
    {
        $this->eventId = $payload['eventId'];
        $this->data = new Collection($payload['data'] ?? []);
        $this->timestamp = time();
    }

    /**
     * Get valu from data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }
}