<?php

namespace App\Anet\Games;

use App\Anet\YouTubeHelpers;
interface GameInterface
{
    public const NAME = 'GAME';
    public const COMMAND_HELP = '/play';
    public const COMMAND_START = '/play';
    public const DEFAULT_EXPIRE_TIME = 120;

    public function step(string $answer) : array;

    public function checkSession() : ?string;

    public function getInitMessage() : string;

    public function getStatistic() : array;

    public static function getHelpMessage() : array;
}
