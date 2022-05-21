<?php

namespace App\Anet\Games;

use App\Anet\Helpers;
use App\Anet\YouTubeHelpers;

abstract class GameAbstract implements GameInterface
{
    protected const GAME_INIT_MESSAGE = '';
    private Helpers\TimeTracker $timeTracker;
    protected YouTubeHelpers\User $user;
    protected int $score;

    abstract protected function defeat(string $defeatMessage) : array;

    abstract protected function victory(string $victoryMessage) : array;

    public function __construct(YouTubeHelpers\User $user)
    {
        $this->user = $user;
        $this->score = 0;

        $this->timeTracker = new Helpers\TimeTracker();
        $this->timeTracker->trackerStart(static::class);
    }

    public function checkSession() : ?string
    {
        if ($this->checkExpire()) {
            return $this->defeat('Время игры ' . static::NAME . ' вышло')['message'];
        }

        return null;
    }

    public function getInitMessage() : string
    {
        return 'Игрок: ' . $this->user->getName() . ' ' . static::GAME_INIT_MESSAGE;
    }

    public function getStatistic() : array
    {
        return [
            'user_key' => $this->user->getId(),
            'game' => static::NAME,
            'score' =>  $this->score,
            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
    }

    protected function checkExpire() : bool
    {
        return $this->timeTracker->trackerCheck(static::class, static::DEFAULT_EXPIRE_TIME);
    }

    protected function getStatisticMessage(int $rating) : string
    {
        return " —— очки: {$this->score} —— текущий соц рейтинг: $rating";
    }

    protected function end(string $endMessage) : array
    {
        $this->user->incrementRaiting($this->score);

        return [
            'message' => $this->user->getName() . ' ' . $endMessage . $this->getStatisticMessage($this->user->getRating()),
            'end' => true,
        ];
    }
}
