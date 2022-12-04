<?php

namespace Porter\Events;

use Porter\Channel;
use Porter\Connection;
use Porter\Server;
use Porter\Payload;
use Porter\Support\Collection;
use Porter\Traits\Payloadable;
use Respect\Validation\Validator;
use Workerman\Connection\TcpConnection;

abstract class AbstractEvent
{
    use Payloadable;

    /**
     * Incoming connection instance.
     *
     * @var Connection
     */
    public readonly Connection $connection;

    /**
     * Payload instance.
     *
     * @var Payload
     */
    public readonly Payload $payload;

    /**
     * Available if client passed `channelId`.
     *
     * @var Channel|null
     */
    public readonly ?Channel $channel;

    /**
     * Available if client passed `targetId`.
     *
     * @var Connection|null
     */
    public readonly ?Connection $target;

    /**
     * @var Server
     */
    public readonly Server $server;

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
     * Short cut for payload data (as &link).
     *
     * @var Collection
     */
    public Collection $data;

    /**
     * Boot event class after create instance.
     *
     * @param Connection $connection
     * @param Payload $payload
     * @return self
     */
    public function boot(Connection $connection, Payload $payload): self
    {
        $this->connection = $connection;
        $this->payload = $payload;
        $this->server = Server::getInstance();
        $this->data = &$this->payload->data;

        $this->initMagicalVars();

        return $this;
    }

    /**
     * @return void
     */
    protected function initMagicalVars(): void
    {
        // Get channel instance by `channelId` parameter.
        $this->channel = $this->server->channel($this->payload->get('channelId', ''));

        // Get target connection instance by `targetId` parameter.
        $this->target = $this->payload->has('targetId')
            ? $this->server->connection((int) $this->payload->get('targetId'))
            : null;
    }

    /**
     * Handle incoming event from client.
     *
     * @return mixed
     */
    abstract public function handle(Connection $connection, Payload $payload, Server $server);

    /**
     * Send event to connection.
     *
     * @param TcpConnection|Connection|array $connection
     * @param string $event
     * @param array $data
     * @return self
     */
    public function to(TcpConnection|Connection|array $connection, string $event, array $data = []): self
    {
        $this->server->to($connection, $event, $data);

        return $this;
    }

    /**
     * Reply event to incoming connection.
     *
     * @param string|null $event If `null`, reply with current type.
     * @param array $data
     * @return bool|null
     */
    public function reply(string $event = null, array $data = []): ?bool
    {
        return $this->to($this->connection, $event ?? $this->payload->type, $data);
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
     * @param TcpConnection|Connection|int[] $excepts Connection instance or connection id.
     * @return void
     */
    public function broadcast(string $event, array $data = [], array|TcpConnection|Connection $excepts = []): void
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
     * @return Connection|null
     */
    public function target(): ?Connection
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
     * Validate payload data.
     *
     * @param array $rules Pass custom rules. Default use $rules class attribute.
     * @return bool Returns False if has errors.
     */
    protected function validate(array $rules = null): bool
    {
        foreach ($rules ?? $this->rules as $property => $rules) {
            foreach ($rules as $rule) {
                if (!$this->payload->is($rule, $property)) {
                    $rule = ((array) $rule)[0];
                    $this->errors[$property][$rule] = "{$property} failed validation: {$rule}";
                }
            }
        }

        return !$this->hasErrors();
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

    /**
     * Another yet short cut for payload data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function payload(string $key, mixed $default = null): mixed
    {
        return $this->data->get($key, $default);
    }
}
