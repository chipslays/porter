<?php

namespace Porter\Traits;

trait Payloadable
{
    /**
     * @param string $type
     * @param array $data
     * @return string
     */
    protected function makePayload(string $type, array $data): string
    {
        $payload = [
            'type' => $type,
            'timestamp' => time(),
            'data' => $data,
        ];

        return json_encode($payload);
    }
}