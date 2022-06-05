<?php

namespace Anet\App\Helpers;

/**
 * **Timer** -- class for setup general proccesing of time in script
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class Timer
{
    /**
     * Method set sleep for script for specified secunds with specified message on each iteration
     * @param int $sec time of sleep in secunds
     * @param bool $showMess `[optional]` flag if need show message on each iteration
     * @param ?int $iterator `[optional]` set int if need to divide sleep time for more interval with showing message on each of it
     * @param string $message `[optional]` message for divided intervals
     * @return void
     */
    public static function setSleep(int $sec, bool $showMess = false, ?int $iterator = null, string $message = 'Wait...') : void
    {
        if ($showMess) {
            echo "Script fall asleep for $sec seconds\n";
        }

        if ($iterator === null || ($sec / $iterator) <= 1) {
            sleep($sec);
            return;
        }

        $currentIteration = (int) ($sec / $iterator);
        $currentSec = $sec;

        for ($i = 0; $i <= $currentIteration; $i++, $currentSec -= $iterator) {
            if ($currentSec <= 0) {
                break;
            }

            $currentSleep = (($currentSec - $iterator) <= 0) ? $currentSec : $iterator;

            echo "Sleep $currentSleep sec. $message\n";
            sleep($currentSleep);
        }
    }
}
