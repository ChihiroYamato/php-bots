<?php

namespace Anet\App\Storages;

/**
 * **Buffer** -- class for storage in memory any data
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class Buffer
{
    /**
     * @var string[][] $buffer storage of data
     */
    private array $buffer;

    /**
     * Initialize data storage
     * @return void
     */
    public function __construct()
    {
        $this->buffer = [];
    }

    /**
     * **Method** fetch with delete data by node name from storage
     * @param string $node name of node in storage
     * @return string[] data by node name
     */
    public function fetch(string $node) : array
    {
        if (! array_key_exists($node, $this->buffer)) {
            return [];
        }

        $buffer = $this->buffer[$node];
        $this->buffer[$node] = [];

        return $buffer;
    }

    /**
     * **Method** add data to storage by node name
     * @param string $node name of node in storage
     * @param string[] $buffer saving data
     * @return void
     */
    public function add(string $node, array $buffer) : void
    {
        $prepareBuffer = $buffer;
        $prepareBuffer['published'] = (new \DateTime())->format('Y-m-d H:i:s');
        $this->buffer[$node][] = $prepareBuffer;
    }
}
