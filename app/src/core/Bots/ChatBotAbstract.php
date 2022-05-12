<?php

namespace App\Anet\Bots;

use App\Anet;
use App\Anet\Helpers;
use App\Anet\GoogleModules;
use App\Anet\Storages;

abstract class ChatBotAbstract implements BotInterface, BotDebugInterface, StatisticsInterface
{
    use Helpers\UrlHelperTrait, Helpers\ErrorHelperTrait;

    protected int $totalMessageReading;
    protected int $totalMessageSending;
    protected int $totalIterations;
    protected bool $listeningFlag;
    protected GoogleModules\SmallTalkModule $smallTalk;
    protected Storages\Vocabulary $vocabulary;
    protected Storages\Buffer $buffer;
    protected Anet\Games $games;

    public function __construct()
    {
        $this->totalMessageReading = 0;
        $this->totalMessageSending = 0;
        $this->totalIterations = 0;
        $this->listeningFlag = true;
        $this->smallTalk = new GoogleModules\SmallTalkModule();
        $this->buffer = new Storages\Buffer();
        $this->vocabulary = new Storages\Vocabulary();
        $this->games = new Anet\Games();
    }

    abstract protected function prepareSendings(array $chatlist) : array;

    abstract protected function sendingMessages(array $sending) : int;

    abstract protected function sendMessage(string $message) : bool;

    protected function prepareSmartAnswer(string $message, bool $setDefault = true) : string
    {
        $answer = $this->smallTalk->fetchAnswerFromSmallTalk($message);

        if (empty($answer) && $setDefault) {
            $answer = $this->vocabulary->getRandItem('no_answer');
        }

        return $answer;
    }
}
