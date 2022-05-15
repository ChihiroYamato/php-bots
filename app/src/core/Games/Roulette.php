<?php

namespace App\Anet\Games;

use App\Anet\YouTubeHelpers;

class Roulette extends GameAbstract
{
    public const NAME = 'ROULETTE';
    public const COMMAND_HELP = '/play roul';
    public const COMMAND_START = '/play roul s';
    protected const GAME_INIT_MESSAGE = 'введите число от 1 до 6';

    private int $trueValue;

    public function __construct(YouTubeHelpers\User $user)
    {
        parent::__construct($user);

        $this->trueValue = random_int(0, PHP_INT_MAX) % 6 + 1;
    }

    public function step(string $answer) : array
    {
        if ($this->checkExpire()) {
            return $this->defeat('Время игры ' . self::NAME . ' вышло');
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

    public static function getHelpMessage() : array
    {
        return [
            '—— GAME ' . self::NAME . ' —— правила: игроку предлагается угадать число от 1 до 6, в случае выигрыша - соц рейтинг удваивается, в случае проигрыша - уполовинивается',
            '—— старт: введите <' . self::COMMAND_START . '>. на игру отведено ' . self::DEFAULT_EXPIRE_TIME . ' секунд',
        ];
    }
}
