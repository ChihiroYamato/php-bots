<?php

namespace App\Anet\Games;

use App\Anet\YouTubeHelpers;

class Roulette extends GameAbstract
{
    private const DEFAULT_EXPIRE_TIME = 120;
    protected const GAME_INIT_MESSAGE = 'введите число от 1 до 6';
    public const GAME_NAME = 'ROULETTE';

    private int $trueValue;

    public function __construct(YouTubeHelpers\User $user, int $expireTime = self::DEFAULT_EXPIRE_TIME)
    {
        parent::__construct($user, $expireTime);

        $this->trueValue = random_int(1, 6);
    }

    public function step(string $answer) : array
    {
        if ($this->checkExpire()) {
            return $this->defeat('Время игры ' . self::GAME_NAME . ' вышло');
        }

        $value = (int) $answer;

        if ($value === $this->trueValue) {
            return $this->victory('угадал!');
        }

        return $this->defeat('неверно:( правильное число: ' . $this->trueValue);
    }

    protected function victory(string $victoryMessage) : array
    {
        $this->score = $this->user->getRating();

        return $this->end($victoryMessage);
    }

    protected function defeat(string $defeatMessage) : array
    {
        $this->score = -round($this->user->getRating() / 2, 0, PHP_ROUND_HALF_DOWN);

        return $this->end($defeatMessage);
    }
}
