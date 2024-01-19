<?php

namespace Porter\Events;

use Closure;
use Porter\Server\Connection;
use Workerman\Connection\TcpConnection;

class Dispatcher
{
    /**
     * @var array
     */
    protected array $events = [];

    /**
     * Add event to dispatcher.
     *
     * If an event with the same ID has already been added, it will be overwritten.
     *
     * @param Event $event
     * @return void
     */
    public function add(Event $event)
    {
        $this->events[$event->getOrder()][] = $event;
    }

    /**
     * Find event by ID.
     *
     * @param string $id
     * @return Event|null
     */
    public function find(string $id): ?Event
    {
        $events = $this->getAll();

        foreach ($events as $event) {
            if ($id === $event->getId()) {
                return $event;
            }

            if (@preg_match($event->getId(), $id) === 1) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Retrieves all events, sorted in order.
     *
     * @return Event[]
     */
    public function getAll(): array
    {
        $events = $this->events;

        ksort($events);

        return call_user_func_array('array_merge', $events);
    }

    /**
     * Clear all events.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->events = [];

        return $this;
    }

    public function dispatch(TcpConnection $connection, string $data, $rawMessageCallback): void
    {
        // Try decode incoming data.
        $event = json_decode($data, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Handle as Event.
            $this->handleEvent(new Connection($connection), (array) $event, $data, $rawMessageCallback);
        } else {
            // Handle as raw message.
            $this->handleMessage(new Connection($connection), $data, $rawMessageCallback);
        }
    }

    /**
     * @param Connection $connection
     * @param array $payload
     * @param string $data
     * @return void
     */
    protected function handleEvent(Connection $connection, array $event, string $data, ?Closure $rawMessageCallback): void
    {
        // If it not valid a event ID.
        if (empty($event['id']) || trim($event['id']) === '') {
            // Try handle as raw message data.
            $this->handleMessage($connection, $data, $rawMessageCallback);

            return;
        }

        // Find event by ID.
        $eventInstance = $this->find($event['id']);

        // If event not found.
        if (!$eventInstance) {
            return;
        }

        // Trigger event callback.
        $eventInstance($connection, new Payload((array) @$event['data']));
    }

    /**
     * @param Connection $connection
     * @param string $data
     * @return void
     */
    protected function handleMessage(Connection $connection, string $data, ?Closure $rawMessageCallback): void
    {
        // If message callback not set.
        if (!$rawMessageCallback) {
            return;
        }

        // Trigger message callback.
        call_user_func_array($rawMessageCallback, [$connection, $data]);
    }
}