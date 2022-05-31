<?php

namespace Anet\App\YouTubeHelpers;

/**
 * **UserInterface** -- interface of project user wrapper
 */
interface UserInterface
{
    /**
     * **Method** return id of user
     * @return string id of user
     */
    public function getId() : string;

    /**
     * **Method** return name of user
     * @return string name of user
     */
    public function getName() : string;

    /**
     * **Method** checked if user is active
     * @return bool return true if user is active
     */
    public function checkActive() : bool;

    /**
     * **Method** checked if user is admin
     * @return bool return true if user is admin
     */
    public function checkAdmin() : bool;

    /**
     * **Method** return message count
     * @return int message count
     */
    public function getMessages() : int;

    /**
     * **Method** return value of current rating
     * @return int current rating
     */
    public function getRating() : int;

    /**
     * **Method** increment message count
     * @param int $count count of message
     * @return void
     */
    public function incrementMessage(int $count) : void;

    /**
     * **Method** increment value of current rating
     * @param int $count value of new rating
     * @return void
     */
    public function incrementRaiting(int $count) : void;
}
