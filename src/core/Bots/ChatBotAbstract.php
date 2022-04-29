<?php

namespace Anet\App\Bots;

use Anet\App\Bots\Interfaces;
use Anet\App\Bots\Traits;

abstract class ChatBotAbstract implements Interfaces\BotInterface, Interfaces\BotDebugInterface
{
    use Traits\SmallTalkModuleTrait;

    abstract protected function prepareMessages(array $chatlist) : int;

    abstract protected function sendMessage(string $message) : bool;

    protected function prepareSmartAnswer(string $message, bool $setDefault = true) : string
    {
        $answer = $this->fetchAnswerFromSmallTalk($message);

        if (empty($answer) && $setDefault) {
            $answer = 'спроси что попроще';
        }

        return $answer;
    }
}
