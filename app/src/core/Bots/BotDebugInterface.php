<?php

namespace App\Anet\Bots;

/**
 * **BotDebugInterface** -- interface for debuging connect to server
 */
interface BotDebugInterface
{
    /**
     * **Method** for test connect and fetch data
     * @return void
     */
    public function testConnect() : void;

    /**
     * **Method** for test connect and send data
     * @return void
     */
    public function testSend() : void;
}
