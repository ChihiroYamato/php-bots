<?php

namespace App\Anet\Bots;

abstract class ChatBotAbstract implements BotInterface, BotDebugInterface, StatisticsInterface
{
    use SmallTalkModuleTrait;

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
            $answer = $this->getVocabulary()['no_answer']['response'][random_int(0, count($this->getVocabulary()['no_answer']['response']) - 1)];
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

    protected function fetchBuffer(string $node) : array
    {
        if (! array_key_exists($node, $this->buffer)) {
            return [];
        }

        $buffer = $this->buffer[$node];
        $this->buffer[$node] = [];

        return $buffer;
    }

    protected function addBuffer(string $node, array $buffer) : void
    {
        $prepareBuffer = $buffer;
        $prepareBuffer['time'] = (new \DateTime())->format('H:i:s');
        $this->buffer[$node][] = $prepareBuffer;
    }
}
