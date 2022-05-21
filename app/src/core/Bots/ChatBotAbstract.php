<?php

namespace App\Anet\Bots;

use App\Anet;
use App\Anet\Helpers;
use App\Anet\GoogleModules;
use App\Anet\Storages;

abstract class ChatBotAbstract implements BotInterface, BotDebugInterface, StatisticsInterface
{
    use Helpers\UrlTrait, Helpers\ErrorTrait;

    protected string $className;
    protected int $totalMessageReading;
    protected int $totalMessageSending;
    protected int $totalIterations;
    protected bool $listeningFlag;
    protected GoogleModules\SmallTalk $smallTalk;
    protected Helpers\TimeTracker $timeTracker;
    protected Storages\Vocabulary $vocabulary;
    protected Storages\Buffer $buffer;
    protected Anet\Games $games;

    abstract protected function prepareSendings(array $chatlist) : array;

    abstract protected function sendingMessages(array $sending) : int;

    abstract protected function sendMessage(string $message) : bool;

    public function __construct()
    {
        $this->className = basename(str_replace('\\', '/', static::class), 'Bot');
        $this->totalMessageReading = 0;
        $this->totalMessageSending = 0;
        $this->totalIterations = 0;
        $this->listeningFlag = true;
        $this->smallTalk = new GoogleModules\SmallTalk();
        $this->timeTracker = new Helpers\TimeTracker();
        $this->buffer = new Storages\Buffer();
        $this->vocabulary = new Storages\Vocabulary();
        $this->games = new Anet\Games();
    }

    public function getStatistics() : array
    {
        return [
            'timeStarting' => $this->timeTracker->getTimeInit(),
            'timeProccessing' => $this->timeTracker->getDuration(),
            'messageReading' => $this->totalMessageReading,
            'messageSending' => $this->totalMessageSending,
            'iterations' => $this->totalIterations,
            'iterationAverageTime' => $this->timeTracker->sumPointsAverage(),
            'iterationMinTime' => $this->timeTracker->fetchMinIteration(),
            'iterationMaxTime' => $this->timeTracker->fetchMaxIteration(),
        ];
    }

    protected function prepareSmartAnswer(string $message, bool $setDefault = true) : string
    {
        $answer = $this->smallTalk->fetchAnswerFromSmallTalk($message);

        if (empty($answer) && $setDefault) {
            $answer = $this->vocabulary->getRandItem('no_answer');
        }

        return $answer;
    }
}
