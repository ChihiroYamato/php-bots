<?php

namespace App\Anet;

final class Games
{
    private array $storage;
    private array $restList;

    public function __construct()
    {
        $this->storage = [];
        $this->restList = [];
    }

    public function validateAndStarting(Games\GameInterface $game, YouTubeHelpers\User $user, int $timeout, int $minRaiting = 1) : array
    {
        if (isset($this->restList[$user->getId()]) && isset($this->restList[$user->getId()][$game::class]) && Helpers\TimeTracker::calculateDuration($this->restList[$user->getId()][$game::class]) < $timeout) {
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

    public function checkUserActiveSession(string $userId) : bool
    {
        return (! empty($this->storage) && array_key_exists($userId, $this->storage));
    }

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

    private function start(Games\GameInterface $game, string $userId) : string
    {
        $this->storage[$userId] = $game;

        return $game->getInitMessage();
    }

    private function closeUserSession(string $userId) : void
    {
        $this->restList[$userId][$this->storage[$userId]::class] = hrtime(true);
        DB\DataBase::saveGameStatistic($this->storage[$userId]->getStatistic());
        unset($this->storage[$userId]);
    }
}
