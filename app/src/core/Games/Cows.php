<?php

namespace Anet\App\Games;

use Anet\App\YouTubeHelpers;

/**
 * **Cows** -- class realization of game Bulls and Cows
 */
class Cows extends Game
{
    public const NAME = 'COWS';
    public const COMMAND_HELP = '/play cows';
    public const COMMAND_START = '/play cows s';
    public const DEFAULT_EXPIRE_TIME = 1200;
    protected const GAME_INIT_MESSAGE = 'выберите уровень сложности';

    /**
     * @var array `private` list of game difficults mode
     */
    private const DIFFICULT = [1 => 'easy', 2 => 'normal', 3 => 'hard', 4 => 'insane'];
    /**
     * @var array `private` list of description for difficults mode
     */
    private const DESCRIPTION = ['easy' => '4 цифры от 1 до 8', 'normal' => '5 цифр от 1 до 0', 'hard' => '6 цифр от 1 до b', 'insane' => '8 цифр от 1 до f'];
    /**
     * @var array `private` list score difficult modifer
     */
    private const MODIFIER = ['easy' => 1, 'normal' => 1.5, 'hard' => 2.5, 'insane' => 5];
    /**
     * @var array `private` base victory score list
     */
    private const VICTORY_SCORE = [350, 300, 250, 200, 150];
    /**
     * @var array `private` base defeat score
     */
    private const DEFEAT_SCORE = -50;

    /**
     * @var null|string $difficult `private` current difficult of game session
     */
    private ?string $difficult;

    /**
     * @var int $steps `private` count of user steps
     */
    private int $steps;

    /**
     * @var string $StringNumber `private` current number to win game
     */
    private string $StringNumber;

    public function __construct(YouTubeHelpers\User $user)
    {
        parent::__construct($user);

        $this->difficult = null;
        $this->steps = 0;
        $this->StringNumber = '';
    }

    public static function getHelpMessage() : array
    {
        return [
            sprintf('—— GAME %s —— Нужно отгадать загаданное компьютером число. Если цифра есть в загаданном числе и стоит на своем месте - это бык. Если есть, но стоит в другом месте - это корова.', self::NAME),
            sprintf('Волки - цифры, которых нет в числе. —— Уровни сложности: [%s]', implode(', ', array_map(fn ($key, $value) => "$key => $value: " . self::DESCRIPTION[$value], array_keys(self::DIFFICULT), array_values(self::DIFFICULT)))),
            sprintf('—— модификатор очков: [%s] —— старт: введите <%s> на игру отведено %s секунд.', implode(', ', array_map(fn ($value) => "$value: x" . self::MODIFIER[$value], array_values(self::DIFFICULT))), self::COMMAND_START, self::DEFAULT_EXPIRE_TIME),
        ];
    }

    public function step(string $answer) : array
    {
        if ($this->checkExpire()) {
            return $this->defeat('Время игры ' . self::NAME . ' вышло');
        }

        if ($this->difficult === null) {
            if (! array_key_exists((int) $answer, self::DIFFICULT)) {
                return $this->defeat('Нет такого уровня сложности');
            }

            $this->difficult = self::DIFFICULT[(int) $answer];
            $this->StringNumber = $this->generateNumber();

            return [
                'message' => sprintf('%s Число сформировано с параметрами: %s. Угадайте число.', $this->user->getName(), self::DESCRIPTION[$this->difficult]),
                'end' => false,
            ];
        }

        $this->steps++;

        switch (true) {
            case $answer === $this->StringNumber:
                return $this->victory('');
            case strlen($answer) !== strlen($this->StringNumber):
                return $this->defeat('неверная разрядность числа');
            default:
                return [
                    'message' => sprintf('%s, %s', $this->user->getName(), $this->getDetail($answer)),
                    'end' => false,
                ];
        }
    }

    protected function victory(string $victoryMessage) : array
    {
        $score = 0;
        $message = $victoryMessage;

        switch (true) {
            case $this->steps < 6:
                $score = self::VICTORY_SCORE[0];
                $message .= sprintf('Легендарная победа! По мудрости ты подобен Стетхему! ходов: %s.', $this->steps);
                break;
            case $this->steps < 9:
                $score = self::VICTORY_SCORE[1];
                $message .= sprintf('Угадал! Отличный результат! Ты что, учился играть у корейцев? ходов: %s.', $this->steps);
                break;
            case $this->steps < 13:
                $score = self::VICTORY_SCORE[2];
                $message .= sprintf('Угадал! ходов: %s.', $this->steps);
                break;
            case $this->steps < 21:
                $score = self::VICTORY_SCORE[3];
                $message .= sprintf('Трудная победа! ходов: %s.', $this->steps);
                break;
            default:
                $score = self::VICTORY_SCORE[4];
                $message .= sprintf('Наконец то угадал! Похоже, тебе мешали цыгане. ходов: %s.', $this->steps);
                break;
        }

        $this->score = $score * self::MODIFIER[$this->difficult];

        return $this->end($message);
    }

    protected function defeat(string $defeatMessage) : array
    {
        $this->score = self::DEFEAT_SCORE;

        return $this->end($defeatMessage);
    }

    /**
     * **Method** initiate number to win game by current difficult
     * @return string number to win game
     */
    private function generateNumber() : string
    {
        $result = '';
        $intSize = 0;
        $vocabulary = [];

        switch ($this->difficult) {
            case self::DIFFICULT[1]:
                $vocabulary = range(1, 8);
                $intSize = 4;
                break;
            case self::DIFFICULT[2]:
                $vocabulary = range(0, 9);
                $intSize = 5;
                break;
            case self::DIFFICULT[3]:
                $vocabulary = array_map(fn (int $int) => dechex($int), range(0, 11));
                $intSize = 6;
                break;
            default:
                $vocabulary = array_map(fn (int $int) => dechex($int), range(0, 16));
                $intSize = 8;
                break;
        }

        for ($i = 0; $i < $intSize; $i++) {
            do {
                $key = random_int(0, count($vocabulary) - 1);
            } while ($vocabulary[$key] === 'close');

            $char = $vocabulary[$key];
            $vocabulary[$key] = 'close';
            $result .= $char;
        }

        return $result;
    }

    /**
     * **Method** get detail by current cows, bulls and wolves for user input
     * @param string $currentNumber user input
     * @return string detail
     */
    private function getDetail(string $currentNumber) : string
    {
        $cows = 0;
        $bulls = 0;
        $length = strlen($currentNumber);

        for ($i = 0; $i < $length; $i++) {
            if ($currentNumber[$i] === $this->StringNumber[$i]) {
                $bulls++;
            } elseif (strpos($this->StringNumber, $currentNumber[$i]) !== false) {
                $cows++;
            }
        }

        return sprintf('быков: %d, коров: %d, волков: %d. Попробуйте снова!', $bulls, $cows, $length - ($cows + $bulls));
    }
}
