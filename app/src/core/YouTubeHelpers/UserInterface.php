<?php

namespace App\Anet\YouTubeHelpers;

interface UserInterface
{
    public function getId() : string;

    public function getName() : string;

    public function checkActive(): bool;

    public function checkAdmin() : bool;

    public function getMessages() : int;

    public function getRating() : int;

    public function incrementMessage(int $count) : void;

    public function incrementRaiting(int $count) : void;
}
