<?php

namespace App\Anet\Bots;

interface BotDebugInterface
{
    public function testConnect() : void;

    public function testSend() : void;
}
