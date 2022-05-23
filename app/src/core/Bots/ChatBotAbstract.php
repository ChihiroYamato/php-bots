<?php

namespace App\Anet\Bots;

use App\Anet;
use App\Anet\Helpers;
use App\Anet\GoogleModules;
use App\Anet\Storages;

/**
 * **ChatBotAbstract** -- base class for project bots
 */
abstract class ChatBotAbstract implements BotInterface, BotDebugInterface, StatisticsInterface
{
    use Helpers\UrlTrait, Helpers\ErrorTrait;

    /**
     * @var string $className `protected` short name of current class
     */
    protected string $className;
    /**
     * @var int $totalMessageReading `protected` total count of message which reading by bot
     */
    protected int $totalMessageReading;
    /**
     * @var int $totalMessageSending `protected` total count of message which sending by bot
     */
    protected int $totalMessageSending;
    /**
     * @var int $totalIterations `protected` total count of bot iterations
     */
    protected int $totalIterations;
    /**
     * @var bool $listeningFlag `protected` flag of bot working
     */
    protected bool $listeningFlag;
    /**
     * @var \App\Anet\GoogleModules\SmallTalk $smallTalk `protected` instance of SmallTalk class
     */
    protected GoogleModules\SmallTalk $smallTalk;
    /**
     * @var \App\Anet\Helpers\TimeTracker $timeTracker `protected` instance of TimeTracker class
     */
    protected Helpers\TimeTracker $timeTracker;
    /**
     * @var \App\Anet\Storages\Vocabulary $vocabulary `protected` instance of Vocabulary class
     */
    protected Storages\Vocabulary $vocabulary;
    /**
     * @var \App\Anet\Storages\Buffer $buffer `protected` instance of Buffer class
     */
    protected Storages\Buffer $buffer;
    /**
     * @var \App\Anet\Games $games `protected` instance of Games class
     */
    protected Anet\Games $games;

    /**
     * **Method** prepare list of sending
     * @param array $chatlist current chat list from server
     * @return array list of sending
     */
    abstract protected function prepareSendings(array $chatlist) : array;

    /**
     * **Method** execute sending of message list
     * @param array $sending current message list for sending
     * @return int count of success sending
     */
    abstract protected function sendingMessages(array $sending) : int;

    /**
     * **Method** execute sending of single message
     * @param string $message current message
     * @return bool success of sending
     */
    abstract protected function sendMessage(string $message) : bool;

    /**
     * Base initialize of bot class
     */
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

    /**
     * **Method** prepare answer from smallTalk module
     * @param string $message current message
     * @param bool $setDefault `[optional]` set default answer if module get empty response, default - true
     * @return string answer from smallTalk module
     */
    protected function prepareSmartAnswer(string $message, bool $setDefault = true) : string
    {
        $answer = $this->smallTalk->fetchAnswerFromSmallTalk($message);

        if (empty($answer) && $setDefault) {
            $answer = $this->vocabulary->getRandItem('no_answer');
        }

        return $answer;
    }
}
