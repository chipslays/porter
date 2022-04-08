<?php

namespace Porter;

use Chipslays\Collection\Collection;

class Payload
{
    public readonly string $eventId;

    public readonly int $timestamp;

    public Collection $data;

    /**
     * Constructor.
     *
     * @param array $payload
     */
    public function __construct(public array $payload)
    {
        $this->eventId = $payload['eventId'];
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
}