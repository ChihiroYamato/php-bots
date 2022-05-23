<?php

namespace App\Anet;

/**
 * **Games** -- class controller of game instances
 */
final class Games
{
    /**
     * @var array $storage `private` storage of games sessions
     */
    private array $storage;
    /**
     * @var array $restList `private` list of users timeouts
     */
    private array $restList;

    /**
     * Initialize Games class
     */
    public function __construct()
    {
        $this->storage = [];
        $this->restList = [];
    }

    /**
     * Save game statistics to DB
     */
    public function __destruct()
    {
        DB\DataBase::saveByTableName('games_statistic', DB\Redis::fetch('game*'));
        Helpers\Loger::print('System', 'Games statistics saved');
    }

    /**
     * **method** validate possibility of user to start game session, start if possible, and get status message
     * @param \App\Anet\Games\GameInterface $game instence of game to starting
     * @param \App\Anet\YouTubeHelpers\User $user instance of current user
     * @param int $timeout game timeout for current user
     * @param int $minRaiting `[optional]` min rating for user to validate session
     * @return array list of response messages
     */
    public function validateAndStarting(Games\GameInterface $game, YouTubeHelpers\User $user, int $timeout, int $minRaiting = 1) : array
    {
        if (isset($this->restList[$user->getId()]) && isset($this->restList[$user->getId()][$game::class]) && Helpers\TimeTracker::getDurationFromStamp($this->restList[$user->getId()][$game::class]) < $timeout) {
            unset($game);
            return [$user->getName() . " на игру установлен таймаут в $timeout секунд, попробуйте позже"];
        }

        if ($user->getRating() < $minRaiting) {
            unset($game);
            return [$user->getName() . " недостаточно рейтинга, минимальный рейтинг: $minRaiting"];
        }

        unset($this->restList[$user->getId()][$game::class]);

        return [$this->start($game, $user->getId())];
    }

    /**
     * **Method** check if user session is active by user id
     * @param string $userId user id
     * @return bool return true if user session is exist else - false
     */
    public function checkUserActiveSession(string $userId) : bool
    {
        return (! empty($this->storage) && array_key_exists($userId, $this->storage));
    }

    /**
     * **Methid** check game for next step by user message and return messages to user
     * @param string $userId id of user in game sessions
     * @param string $message mesasge from user for next step
     * @return string message to user with result of step
     */
    public function checkGame(string $userId, string $message) : string
    {
        if (! $this->checkUserActiveSession($userId)) {
            throw new \Exception('Undefined User in Games storage');
        }

        $response = $this->storage[$userId]->step($message);

        if ($response['end'] === true) {
            $this->closeUserSession($userId);
        }

        return $response['message'];
    }

    /**
     * **Method** Check all users sessiins if it's expired
     * @return array lost of response messages to users
     */
    public function checkSessionsTimeOut() : array
    {
        $result = [];

        foreach ($this->storage as $userId => $game) {
            $response = $game->checkSession();

            if ($response !== null) {
                $result[] = $response;
                $this->closeUserSession($userId);
            }
        }

        return $result;
    }

    /**
     * **Method** save new game session and get init message
     * @param \App\Anet\Games\GameInterface $game instance of current game
     * @param string $userId id of current user
     * @return string init message to user
     */
    private function start(Games\GameInterface $game, string $userId) : string
    {
        $this->storage[$userId] = $game;

        return $game->getInitMessage();
    }

    /**
     * **Method** close game session by user id and save statistic
     * @param string $userId id of current user
     * @return void
     */
    private function closeUserSession(string $userId) : void
    {
        $this->restList[$userId][$this->storage[$userId]::class] = hrtime(true);
        DB\Redis::set('game', $this->storage[$userId]->getStatistic());
        unset($this->storage[$userId]);
    }
}
