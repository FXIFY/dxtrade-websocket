<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Managers;

class DxtradeWebsocketCoroutineManager
{
    /**
     * Store for active coroutines
     *
     * @var array<string, mixed>
     */
    protected array $coroutines = [];

    /**
     * Register a coroutine
     */
    public function register(string $name, mixed $coroutine): void
    {
        $this->coroutines[$name] = $coroutine;
    }

    /**
     * Get a coroutine by name
     */
    public function get(string $name): mixed
    {
        return $this->coroutines[$name] ?? null;
    }

    /**
     * Remove a coroutine
     */
    public function remove(string $name): void
    {
        unset($this->coroutines[$name]);
    }

    /**
     * Get all coroutines
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->coroutines;
    }

    /**
     * Clear all coroutines
     */
    public function clear(): void
    {
        $this->coroutines = [];
    }
}
