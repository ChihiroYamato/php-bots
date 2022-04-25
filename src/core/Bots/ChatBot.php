<?php

namespace Anet\App\Bots;

use Anet\App\Bots\Interfaces\Bot;

abstract class ChatBot implements Bot
{
    abstract protected function prepareMessages(array $chatlist) : int;

    abstract protected function sendMessage(string $message) : bool;
}
