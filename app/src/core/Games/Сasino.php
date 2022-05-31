<?php

namespace App\Anet\Games;

use App\Anet\YouTubeHelpers;

/**
 * **Сasino** -- class realization of game Casino
 */
class Сasino extends Game
{
    public const NAME = 'CASINO';
    public const COMMAND_HELP = '/play casino';
    public const COMMAND_START = '/play casino s';
    protected const GAME_INIT_MESSAGE = 'итак, выберите фишку';
    /**
     * @var int `private` min point for score
     */
    private const MIN_POINT = 10;
    /**
     * @var int `private` max point for score
     */
    private const MAX_POINT = 300;
    /**
     * @var int `private` size of casino board
     */
    private const BOARD_SIZE = 42;

    /**
     * @var array $board `private` current board for session
     */
    private array $board;
    /**
     * @var null|int $position `private` current user choice
     */
    private ?int $position;

    public function __construct(YouTubeHelpers\User $user)
    {
        parent::__construct($user);

        $this->board = $this->prepareBoard(self::MIN_POINT, self::MAX_POINT);
        $this->position = null;
    }

    public static function getHelpMessage() : array
    {
        return [
            sprintf('—— GAME %s  —— правила: игроку предлагается выбрать номер фишки от 1 до %d, у каждой фишки есть сумма очков от %d до %d, но половина фишек - выйгрышные, половина - проигрышные', self::NAME, self::BOARD_SIZE, self::MIN_POINT, self::MAX_POINT),
            sprintf('—— старт: введите <%s>. на игру отведено %d секунд', self::COMMAND_START, self::DEFAULT_EXPIRE_TIME),
        ];
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

    /**
     * **Method** init board for current game session
     * @param int $min min score
     * @param int $max max score
     * @return array board
     */
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
}
