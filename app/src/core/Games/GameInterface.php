<?php

namespace App\Anet\Games;

use App\Anet\YouTubeHelpers;
interface GameInterface
{
    public const GAME_NAME = 'GAME';

    public function __construct(object $user, int $expireTime);

    public function step(string $answer) : array;

    public function checkSession() : ?string;

    public function getInitMessage() : string;

    public function getStatistic() : array;
}
