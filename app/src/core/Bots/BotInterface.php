<?php

namespace Anet\App\Bots;

/**
 * **BotInterface** -- base interface for bots
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
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
