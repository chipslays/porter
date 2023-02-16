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
     * Available if client passed `channel_id_`.
     *
     * @var Channel|null
     */
    public readonly ?Channel $channel;

    /**
     * Available if client passed `target_id`.
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
     * @see https://respect-validation.readthedocs.io/en/latest
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Array of validation custom messages.
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Errors bag for validation.
     *
     * @var ErrorBag
     */
    public ErrorBag $errorBag;

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
        $this->errorBag = new ErrorBag;

        $this->setMagicalVariables();

        return $this;
    }

    /**
     * @return void
     */
    protected function setMagicalVariables(): void
    {
        // Get channel instance by `channel_id_` parameter.
        if ($this->data->has('channel_id')) {
            $this->channel = $this->server->channel($this->payload('channel_id'));
        }

        // Get target connection instance by `target_id` parameter.
        if ($this->data->has('target_id')) {
            $this->target = $this->server->connection((int) $this->payload('target_id'));
        }
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
     * @return self
     */
    public function reply(string $event = null, array $data = []): self
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
     * Getter for channel (available if client passed `channel_id_`).
     *
     * @return Channel|null
     */
    public function channel(): ?Channel
    {
        return $this->channel;
    }

    /**
     * Getter for target (available if client passed `target_id`).
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
        return Server::getInstance()->validator();
    }

    /**
     * Validate payload data.
     *
     * @param array $rules Pass custom rules. Default use $rules class attribute.
     * @return bool Returns False if has errors.
     */
    public function validate(array $rules = null): bool
    {
        foreach ($rules ?? $this->rules as $property => $rules) {
            foreach ($rules as $rule) {
                if (!$this->payload->is($rule, $property)) {
                    // get a rule name
                    $rule = ((array) $rule)[0];

                    // get a message
                    $message = @$this->messages[$property][$rule]
                        ?  str_replace(['%prop%', '%property%'], $property, $this->messages[$property][$rule])
                        : "Property {$property} failed validation {$rule} rule.";

                    // add error to errors bag
                    $this->errorBag()->add($property, $rule, $message);
                }
            }
        }

        return !$this->hasErrors();
    }

    /**
     * Get a errors bag.
     *
     * @return ErrorBag
     */
    public function errorBag(): ErrorBag
    {
        return $this->errorBag;
    }

    /**
     * Returns `true` if has errors on validate payload data.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errorBag()->any();
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
