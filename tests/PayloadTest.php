<?php

use Porter\Payload;

$payload = new Payload([
    'eventId' => 'example event',
    'timestamp' => time(),
    'data' => [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com',
        'age' => 25,
        'photo' => [
            'xl' => 'image-xl.jpg',
        ]
    ],
]);

it('event id', function () use ($payload) {
    expect($payload->eventId)->toEqual('example event');
});

it('event timestamp', function () use ($payload) {
    expect($payload->timestamp)->toBeInt();
});

it('event data', function () use ($payload) {
    expect($payload->data['firstname'])->toEqual('John');
    expect($payload->data->firstname)->toEqual('John');

    // nested
    expect($payload->data->photo['xl'])->toEqual('image-xl.jpg');
    expect($payload->data['photo']['xl'])->toEqual('image-xl.jpg');
});
