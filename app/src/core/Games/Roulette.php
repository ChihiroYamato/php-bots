<?php

namespace App\Anet\Games;

use App\Anet\Helpers;
use App\Anet\YouTubeHelpers;

class Roulette implements GameInterface
{
    private const DEFAULT_EXPIRE_TIME = 120;
    public const GAME_NAME = 'ROULETTE';

    private Helpers\TimeTracker $timeTracker;
    private YouTubeHelpers\User $user;
    private int $expireTime;
    private int $trueValue;
    private int $score;

    public function __construct(YouTubeHelpers\User $user, int $expireTime = self::DEFAULT_EXPIRE_TIME)
    {
        $this->user = $user;
        $this->expireTime = $expireTime;
        $this->trueValue = random_int(1, 6);
        $this->score = 0;
        $this->timeTracker = new Helpers\TimeTracker();

        $this->timeTracker->trackerStart(self::class);
    }

    public function check(string $answer) : array
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

    public function checkSession() : ?string
    {
        if ($this->checkExpire()) {
            return $this->defeat('Время игры ' . self::GAME_NAME . ' вышло')['message'];
        }

        return null;
    }

    public function getInitMessage() : string
    {
        return 'Игрок: ' . $this->user->getName() . ' введите число от 1 до 6';
    }

    public function getStatistic() : array
    {
        return [
            ':game' => self::GAME_NAME,
            ':user_key' => $this->user->getId(),
            ':score' =>  $this->score,
            ':date' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
    }

    private function victory(string $victoryMessage) : array
    {
        $this->score = $this->user->getRating();
        $this->user->incrementRaiting($this->score);

        return [
            'message' => $this->user->getName() . ' ' . $victoryMessage . $this->getStatisticMessage($this->user->getRating()),
            'end' => true,
        ];
    }

    private function defeat(string $defeatMessage) : array
    {
        $this->score = -round($this->user->getRating() / 2, 0, PHP_ROUND_HALF_DOWN);
        $this->user->incrementRaiting($this->score);

        return [
            'message' => $this->user->getName() . ' ' . $defeatMessage . $this->getStatisticMessage($this->user->getRating()),
            'end' => true,
        ];
    }

    private function getStatisticMessage(int $rating) : string
    {
        return " —— очки: {$this->score} —— текущий соц рейтинг: $rating";
    }

    private function checkExpire() : bool
    {
        return $this->timeTracker->trackerCheck(self::class, $this->expireTime);
    }
}
