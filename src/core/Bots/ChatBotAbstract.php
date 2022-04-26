<?php

namespace Anet\App\Bots;

use Anet\App\Bots\Interfaces;

abstract class ChatBotAbstract implements Interfaces\BotInterface
{
    abstract protected function prepareMessages(array $chatlist) : int;

    abstract protected function sendMessage(string $message) : bool;

    protected function prepareSmartAnswer(string $message) : string
    {
        return $message;
    }
}
