<?php

namespace Anet\App\Bots;

use Anet\App\Bots\Interfaces;
use Anet\App\Bots\Traits;

abstract class ChatBotAbstract implements Interfaces\BotInterface, Interfaces\BotDebugInterface, Interfaces\StatisticsInterface
{
    use Traits\SmallTalkModuleTrait;

    protected int $totalMessageReading = 0;
    protected int $totalMessageSending = 0;
    protected int $totalIterations = 0;
    protected ?array $vocabulary = null;
    protected array $buffer = [];
    protected bool $listeningFlag = true;

    abstract protected function prepareSendings(array $chatlist) : array;

    abstract protected function sendingMessages(array $sending) : int;

    abstract protected function sendMessage(string $message) : bool;

    protected function prepareSmartAnswer(string $message, bool $setDefault = true) : string
    {
        $answer = $this->fetchAnswerFromSmallTalk($message);

        if (empty($answer) && $setDefault) {
            $answer = $this->getVocabulary()['no_answer']['response'][rand(0, count($this->getVocabulary()['no_answer']['response']) - 1)];
        }

        return $answer;
    }

    protected function getVocabulary() : array
    {
        if ($this->vocabulary === null) {
            $this->vocabulary = [
                'standart' => require_once VOC_STANDART,
                'no_answer' => require_once VOC_NO_ANSWER,
                'no_care' => require_once VOC_NO_CARE,
                'dead_chat' => require_once VOC_DEAD_CHAT,
                'dead_inside' => require_once VOC_DEAD_INSIDE,
                'another' => require_once VOC_ANOTHER,
            ];
        }

        return $this->vocabulary;
    }

    protected function fetchBuffer() : array
    {
        $buffer = $this->buffer;
        $this->buffer = [];

        return $buffer;
    }

    protected function addBuffer(array $buffer) : void
    {
        $prepareBuffer = $buffer;
        $prepareBuffer['time'] = (new \DateTime())->format('H:i:s');
        $this->buffer[] = $prepareBuffer;
    }
}
