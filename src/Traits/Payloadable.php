<?php

namespace Porter\Traits;

trait Payloadable
{
    /**
     * @param string $event
     * @param array $data
     * @return string
     */
    protected function makePayload(string $event, array $data): string
    {
        $payload = [
            'eventId' => $event,
            'timestamp' => time(),
            'data' => $data,
        ];

        return json_encode($payload);
    }
}