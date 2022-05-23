<?php

namespace App\Anet\Games;

use App\Anet\Contents;
use App\Anet\YouTubeHelpers;

class Towns extends Game
{
    public const DEFAULT_EXPIRE_TIME = 360;
    public const NAME = 'TOWNS';
    public const COMMAND_HELP = '/play towns';
    public const COMMAND_START = '/play towns s';
    protected const GAME_INIT_MESSAGE = 'назовите город';
    /**
     * @var int `private` max step of game
     */
    private const MAX_TURN = 15;
    /**
     * @var int `private` count of victory letters
     */
    private const LETTERS_COUNT = 3;
    /**
     * @var int `private` score of victory
     */
    private const WIN_SCORE = 300;
    /**
     * @var int `private` score of defeat
     */
    private const LOSE_SCORE = 50;

    private array $winLetters;
    private array $stopList;
    private int $steps;
    private ?string $lastLetter;

    public function __construct(YouTubeHelpers\User $user)
    {
        parent::__construct($user);

        $this->winLetters = $this->getLetters(Contents\Cities::VOCABULARY);
        $this->stopList = [];
        $this->steps = 0;
        $this->lastLetter = null;
    }

    public function step(string $answer) : array
    {
        switch (true) {
            case $this->checkExpire():
                return $this->defeat('Время игры ' . self::NAME . ' вышло');
            case $this->lastLetter !== null && $this->lastLetter !== mb_strtolower(mb_strcut($answer, 0, 2)):
                return $this->defeat("Предыдущая буква была: <{$this->lastLetter}>, вы проиграли, а буквы были: "  . implode(', ', $this->winLetters));
            case ! Contents\Cities::validate($answer):
                return $this->defeat("Города <$answer> - не существует, вы проиграли, а буквы были: "  . implode(', ', $this->winLetters));
            case in_array($answer, $this->stopList):
                return $this->defeat('Такой город уже был, вы проиграли, а буквы были: '  . implode(', ', $this->winLetters));
        }

        $this->stopList[] = $answer;
        $letter = mb_strtolower(mb_strcut($answer, -1));
        $this->steps++;

        if (in_array($letter, $this->winLetters)) {
            return $this->victory('Вы угадали букву! а буквы были: ' . implode(', ', $this->winLetters));
        }

        if ($this->steps >= self::MAX_TURN) {
            return $this->defeat('количество ходов истекло :( а буквы были: ' . implode(', ', $this->winLetters));
        }

        $response = $this->getCity($letter);
        $this->stopList[] = $response;
        $this->lastLetter = mb_strtolower(mb_strcut($response, -1));

        return [
            'message' => $this->user->getName() . " продолжаем, $response, тебе на <{$this->lastLetter}>",
            'end' => false,
        ];
    }

    public static function getHelpMessage() : array
    {
        return [
            '—— GAME ' . self::NAME . ' —— правила: классическая игра в города с модификацией: игра ограничена ' . self::MAX_TURN . ' ходами игрока, за это время нужно угадать случайную букву, на которую город не должен заканчиваться',
            'Таких букв всего: ' . self::LETTERS_COUNT . ' —— старт: введите <' . self::COMMAND_START . '>. на игру отведено ' . self::DEFAULT_EXPIRE_TIME . ' секунд, очков за победу: +' . self::WIN_SCORE . ', очков за поражение: -' . self::LOSE_SCORE,
        ];
    }

    protected function victory(string $victoryMessage) : array
    {
        $this->score = self::WIN_SCORE;

        return $this->end($victoryMessage);
    }

    protected function defeat(string $defeatMessage) : array
    {
        $this->score = -self::LOSE_SCORE;

        return $this->end($defeatMessage);
    }

    /**
     * **Method** fetch city by letter from Cities service until geting unique city
     * @param string $letter first letter of city
     * @return string name of city
     */
    private function getCity(string $letter) : string
    {
        do {
            $city = Contents\Cities::getRandByLetter($letter);
        } while (in_array($city, $this->stopList));

        return (! empty($city)) ? $city : '----';
    }

    /**
     * **Method** init victory letters
     * @param array $vocabulary current vocabulary for victory letters
     * @return array victory letters
     */
    private function getLetters(array $vocabulary) : array
    {
        $result = [];

        while (count($result) < self::LETTERS_COUNT) {
            $letter = mb_strtolower($vocabulary[random_int(0, count($vocabulary) - 1)]);

            if (! in_array($letter, $result)) {
                $result[] = $letter;
            }
        }

        return $result;
    }
}
