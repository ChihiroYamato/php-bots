<?php

namespace App\Anet\Storages;

final class Buffer
{
    private array $buffer;

    public function __construct()
    {
        $this->buffer = [];
    }

    public function fetch(string $node) : array
    {
        if (! array_key_exists($node, $this->buffer)) {
            return [];
        }

        $buffer = $this->buffer[$node];
        $this->buffer[$node] = [];

        return $buffer;
    }

    public function add(string $node, array $buffer) : void
    {
        $prepareBuffer = $buffer;
        $prepareBuffer['time'] = (new \DateTime())->format('H:i:s');
        $this->buffer[$node][] = $prepareBuffer;
    }
}
