<?php

namespace App\Anet\Games;

interface GameInterface
{
    public const GAME_NAME = 'GAME';

    public function check(string $answer) : array;

    public function checkSession() : ?string;

    public function getInitMessage() : string;

    public function getStatistic() : array;
}
