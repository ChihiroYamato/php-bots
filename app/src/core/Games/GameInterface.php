<?php

namespace Anet\App\Games;

/**
 * **GameInterface** -- base interface for game classes
 */
interface GameInterface
{
    /**
     * @var string `public` base name of game
     */
    public const NAME = 'GAME';
    /**
     * @var string `public` base command to get help message
     */
    public const COMMAND_HELP = '/play';
    /**
     * @var string `public` base command to start game
     */
    public const COMMAND_START = '/play s';
    /**
     * @var int `public` time of game expire in seconds
     */
    public const DEFAULT_EXPIRE_TIME = 120;

    /**
     * **Method** execute step of game by user answer and return params list
     * @param string $answer user message to game
     * @return array list of params, include 'message' - answer to user, 'end' - flag of game over
     */
    public function step(string $answer) : array;

    /**
     * **Method** check session and return message
     * @return null|string message for user if session failed, else - null
     */
    public function checkSession() : ?string;

    /**
     * **Method** get message of game start for user
     * @return string message of game start
     */
    public function getInitMessage() : string;

    /**
     * **Method** get statistic of game session
     * @return array statistic of game session
     */
    public function getStatistic() : array;

    /**
     * **Method** get help message for user
     * @return array list of messages
     */
    public static function getHelpMessage() : array;
}
