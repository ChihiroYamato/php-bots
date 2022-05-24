<?php

namespace App\Anet\Bots;

/**
 * **BotInterface** -- base interface for bots
 */
interface BotInterface
{
    /**
     * **Method** setup bot listening
     * @param int $interval interval for script sleeping
     * @return void
     */
    public function listen(int $interval) : void;
}
