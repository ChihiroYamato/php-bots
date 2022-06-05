<?php

namespace Anet\App\Bots;

/**
 * **BotDebugInterface** -- interface for debuging connect to server
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
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
