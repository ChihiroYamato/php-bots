<?php

namespace Anet\App\Bots\Interfaces;

interface BotInterface
{
    public function listen(int $interval) : void;
}
