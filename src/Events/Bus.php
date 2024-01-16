<?php

namespace Porter\Events;

class Bus
{
    /**
     * @var array
     */
    protected array $events = [];

    /**
     * Add event to bus.
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
}