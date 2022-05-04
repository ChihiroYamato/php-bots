<?php

namespace App\Anet\Bots;

interface BotInterface
{
    public function listen(int $interval) : void;
}
