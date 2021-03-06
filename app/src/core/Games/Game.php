<?php

namespace Anet\App\Games;

use Anet\App\Helpers;
use Anet\App\YouTubeHelpers;

abstract class Game implements GameInterface
{
    /**
     * @var string `protected` base init message
     */
    protected const GAME_INIT_MESSAGE = '';
    /**
     * @var \Anet\App\Helpers\TimeTracker $timeTracker `private` instance of TimeTracker class
     */
    private Helpers\TimeTracker $timeTracker;
    /**
     * @var \Anet\App\YouTubeHelpers\User $user `protected` instance of User class
     */
    protected YouTubeHelpers\User $user;
    /**
     * @var int $score final score of game
     */
    protected int $score;

    /**
     * **Method** for setup defeat scenario
     * @param string $defeatMessage defeating message of game
     * @return array return params of game session, include 'message' - answer to user, 'end' - flag of game over
     */
    abstract protected function defeat(string $defeatMessage) : array;

    /**
     * **Method** for setup victory scenario
     * @param string $victoryMessage victorying message of game
     * @return array return params of game session, include 'message' - answer to user, 'end' - flag of game over
     */
    abstract protected function victory(string $victoryMessage) : array;

    /**
     * Initialize game session
     * @param \Anet\App\YouTubeHelpers\User $user instance of current user
     * @return void
     */
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
        return sprintf('Игрок: %s %s', $this->user->getName(), static::GAME_INIT_MESSAGE);
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

    /**
     * **Method** check if game is expired
     * @return bool return true if game is expired, else - false
     */
    protected function checkExpire() : bool
    {
        return $this->timeTracker->trackerCheck(static::class, static::DEFAULT_EXPIRE_TIME);
    }

    /**
     * **Method** get message with current statistic of game session
     * @param int $rating current rating of user
     * @return string current statistic of game session
     */
    protected function getStatisticMessage(int $rating) : string
    {
        return "—— очки: {$this->score} —— текущий соц рейтинг: $rating";
    }

    /**
     * **Method** setup game over
     * @param string $endMessage message of game over
     * @return array return params of game session, include 'message' - answer to user, 'end' - flag of game over
     */
    protected function end(string $endMessage) : array
    {
        $this->user->incrementRaiting($this->score);

        return [
            'message' => sprintf('%s %s %s', $this->user->getName(), $endMessage, $this->getStatisticMessage($this->user->getRating())),
            'end' => true,
        ];
    }
}
