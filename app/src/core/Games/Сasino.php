<?php

namespace App\Anet\Games;

use App\Anet\YouTubeHelpers;

class Сasino extends GameAbstract
{
    public const NAME = 'CASINO';
    public const COMMAND_HELP = '/play casino';
    public const COMMAND_START = '/play casino s';
    public const MIN_POINT = 10;
    public const MAX_POINT = 300;
    public const BOARD_SIZE = 42;
    protected const GAME_INIT_MESSAGE = 'итак, выберите фишку';

    private array $board;
    private ?int $position;

    public function __construct(YouTubeHelpers\User $user)
    {
        parent::__construct($user);

        $this->board = $this->prepareBoard(self::MIN_POINT, self::MAX_POINT);
        $this->position = null;
    }

    public function step(string $answer) : array
    {
        if ($this->checkExpire()) {
            return $this->defeat('Время игры ' . self::NAME . ' вышло');
        }

        $value = (int) $answer;
        $this->position = $value - 1;

        if (array_key_exists($this->position, $this->board)) {
           return $this->victory("ваш выбор $value, поздравляю");
        }

        return $this->defeat('Вы выбрали несуществующую фишку');
    }

    protected function victory(string $victoryMessage) : array
    {
        $this->score = $this->board[$this->position];

        return $this->end($victoryMessage);
    }

    protected function defeat(string $defeatMessage) : array
    {
        $this->score = -self::MAX_POINT;

        return $this->end($defeatMessage);
    }

    private function prepareBoard(int $min, int $max) : array
    {
        $part = (int) ((self::BOARD_SIZE / 2) - 1);
        $interval = (int) (($max - $min) / $part);

        $boardPlus = [$min];
        $boardMinus = [-$min];

        for ($i = 1; $i < $part; $i++) {
            $boardPlus[$i] = $boardPlus[$i-1] + $interval;
        }

        for ($i = 1; $i < $part; $i++) {
            $boardMinus[$i] = $boardMinus[$i-1] - $interval;
        }

        $boardPlus[] = $max;
        $boardMinus[] = -$max;

        $result = array_merge($boardPlus, $boardMinus);
        shuffle($result);

        return $result;
    }

    public static function getHelpMessage() : array
    {
        return [
            '—— GAME ' . self::NAME . ' —— правила: игроку предлагается выбрать номер фишки от 1 до '. self::BOARD_SIZE . ', у каждой фишки есть сумма очков от ' . self::MIN_POINT . ' до ' . self::MAX_POINT . ', но половина фишек - выйгрышные, половина - проигрышные',
            '—— старт: введите <' . self::COMMAND_START . '>. на игру отведено ' . self::DEFAULT_EXPIRE_TIME . ' секунд',
        ];
    }
}
