<?php

namespace Porter\Events;

use Porter\Support\Collection;

/**
 * A class for store and manipulate validation errors.
 */
class ErrorBag
{
    /**
     * Contain errors by property.
     *
     * @var Collection
     */
    protected Collection $errors;

    /**
     * Constructor.
     *
     * @param array $errors
     */
    public function __construct(array $errors = [])
    {
        $this->errors = new Collection($errors);
    }

    /**
     * Get a errors by property.
     *
     * @param string $property
     * @return array|null
     */
    public function get(string $property): ?array
    {
        return $this->errors->get($property);
    }

    /**
     * Property has any errors.
     *
     * @param string $property
     * @return boolean
     */
    public function has(string $property): bool
    {
        return $this->errors->has($property);
    }

    /**
     * Remove property from errors bag.
     *
     * @param string $property
     * @return self
     */
    public function remove(string $property): self
    {
        $this->errors->remove($property);

        return $this;
    }

    /**
     * Get a first error message of property.
     *
     * @param string $property
     * @return string|null
     */
    public function first(string $property): ?string
    {
        $messages = $this->get($property);

        if (is_array($messages) && count($messages) > 0) {
            return array_values($messages)[0];
        }

        return null;
    }

    /**
     * Add new error to errors bag.
     *
     * @param string $property
     * @param string $rule
     * @param string $message
     * @return self
     */
    public function add(string $property, string $rule, string $message): self
    {
        $this->errors->set("{$property}.{$rule}", $message);

        return $this;
    }

    /**
     * Get all errors as array.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->errors->all();
    }

    /**
     * Get count of errors.
     *
     * @return integer
     */
    public function count(): int
    {
        return $this->errors->count();
    }

    /**
     * Has any erorrs.
     *
     * @return boolean
     */
    public function any(): bool
    {
        return $this->errors->count() > 0;
    }

    /**
     * Clear errors bag.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->errors = new Collection;

        return $this;
    }
}