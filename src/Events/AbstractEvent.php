<?php

namespace Porter\Events;

use Porter\Channel;
use Porter\Server;
use Porter\Payload;
use Porter\Traits\Payloadable;
use Respect\Validation\Validator;
use Workerman\Connection\TcpConnection;

abstract class AbstractEvent
{
    use Payloadable;

    /**
     * Available if client passed `channelId`.
     *
     * @var Channel|null
     */
    public ?Channel $channel;

    /**
     * Available if client passed `targetId`.
     *
     * @var TcpConnection|null
     */
    public ?TcpConnection $target;

    /**
     * @var Server
     */
    public Server $server;

    /**
     * Array of rules for payload data.
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Array of validate errors.
     *
     * @var array
     */
    public array $errors = [];

    /**
     * Constructor.
     *
     * @param TcpConnection $connection
     * @param Payload $payload
     */
    public function __construct(
        public TcpConnection $connection,
        public Payload $payload,
    ) {
        $this->server = Server::getInstance();

        // Get channel instance by `channelId` parameter.
        $this->channel = $this->server->channels->get($payload->data['channelId'] ?? '');

        // Get target connection instance by `targetId` parameter.
        $this->target = isset($payload->data['targetId']) ? $this->server->getConnection((int) $payload->data['targetId']) : null;

        $this->validate();
    }

    /**
     * Handle incoming event from client.
     *
     * @return void
     */
    abstract public function handle(TcpConnection $connection, Payload $payload, Server $server): void;

    /**
     * Send event to connection.
     *
     * @param TcpConnection $connection
     * @param string $event
     * @param array $data
     * @return bool|null
     */
    public function to(TcpConnection $connection, string $event, array $data = []): ?bool
    {
        return $this->server->to($connection, $event, $data);
    }

    /**
     * Reply event to incoming connection.
     *
     * @param string|null $event If `null`, reply with current eventId.
     * @param array $data
     * @return bool|null
     */
    public function reply(string $event = null, array $data = []): ?bool
    {
        return $this->to($this->connection, $event ?? $this->payload->eventId, $data);
    }

    /**
     * Send raw data to connection.
     *
     * @param string $string
     * @return bool|null
     */
    public function raw(string $string): ?bool
    {
        return $this->connection->send($string);
    }

    /**
     * Send event to all connections.
     *
     * @param string $event
     * @param array $data
     * @param TcpConnection[] $excepts Connection instance or connection id.
     * @return void
     */
    public function broadcast(string $event, array $data = [], array $excepts = []): void
    {
        $this->server->broadcast($event, $data, $excepts);
    }

    /**
     * Getter for channel (available if client passed `channelId`).
     *
     * @return Channel|null
     */
    public function channel(): ?Channel
    {
        return $this->channel;
    }

    /**
     * Getter for target (available if client passed `targetId`).
     *
     * @return TcpConnection|null
     */
    public function target(): ?TcpConnection
    {
        return $this->target;
    }

    /**
     * Create validator instance.
     *
     * @return Validator
     */
    public function validator(): Validator
    {
        return Server::getInstance()->validator::create();
    }

    /**
     * Validate payload data by `$rules`.
     *
     * @return void
     */
    protected function validate(): void
    {
        foreach ($this->rules as $property => $rules) {
            foreach ($rules as $rule) {
                if (!$this->payload->is($rule, $property)) {
                    $rule = ((array) $rule)[0];
                    $this->errors[$property][$rule] = "{$property} failed validation: {$rule}";
                }
            }
        }
    }

    /**
     * Returns `true` if has errors on validate payload data.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
