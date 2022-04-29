<?php

namespace Anet\App\Bots\Interfaces;

interface BotDebugInterface
{
    public function testConnect() : void;

    public function testSend() : void;
}
