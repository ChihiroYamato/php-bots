<?php

namespace Anet\App\Bots\Interfaces;

interface Bot
{
    public function listen(int $interval) : void;
}
