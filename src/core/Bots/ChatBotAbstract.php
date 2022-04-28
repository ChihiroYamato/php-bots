<?php

namespace Anet\App\Bots;

use Anet\App\Bots\Interfaces;
use Anet\App\Bots\Traits;

abstract class ChatBotAbstract implements Interfaces\BotInterface
{
    use Traits\SmallTalkModuleTrait;

    abstract protected function prepareMessages(array $chatlist) : int;

    abstract protected function sendMessage(string $message) : bool;

    protected function prepareSmartAnswer(string $message) : string
    {
        $answer = $this->fetchAnswerFromSmallTalk($message);

        if (empty($answer)) {
            $answer = 'спроси что попроще';
        }

        return $answer;
    }
}
