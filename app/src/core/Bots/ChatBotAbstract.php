<?php

namespace Anet\App\Bots;

use Anet\App;
use Anet\App\Helpers;
use Anet\App\GoogleModules;
use Anet\App\Storages;

/**
 * **ChatBotAbstract** -- base class for project bots
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
abstract class ChatBotAbstract implements BotInterface, BotDebugInterface, StatisticsInterface
{
    use Helpers\UrlTrait, Helpers\ErrorTrait;

    /**
     * @var string $className short name of current class
     */
    protected string $className;
    /**
     * @var int $totalMessageReading total count of message which reading by bot
     */
    protected int $totalMessageReading;
    /**
     * @var int $totalMessageSending total count of message which sending by bot
     */
    protected int $totalMessageSending;
    /**
     * @var int $totalIterations total count of bot iterations
     */
    protected int $totalIterations;
    /**
     * @var bool $listeningFlag flag of bot working
     */
    protected bool $listeningFlag;
    /**
     * @var \Anet\App\GoogleModules\SmallTalk $smallTalk instance of SmallTalk class
     */
    protected GoogleModules\SmallTalk $smallTalk;
    /**
     * @var \Anet\App\Helpers\TimeTracker $timeTracker instance of TimeTracker class
     */
    protected Helpers\TimeTracker $timeTracker;
    /**
     * @var \Anet\App\Storages\Vocabulary $vocabulary instance of Vocabulary class
     */
    protected Storages\Vocabulary $vocabulary;
    /**
     * @var \Anet\App\Storages\Buffer $buffer instance of Buffer class
     */
    protected Storages\Buffer $buffer;
    /**
     * @var \Anet\App\Games $games instance of Games class
     */
    protected App\Games $games;

    /**
     * **Method** prepare list of sending
     * @param string[][] $chatlist current chat list from server
     * @return string[][] list of sending
     */
    abstract protected function prepareSendings(array $chatlist) : array;

    /**
     * **Method** execute sending of message list
     * @param string[][] $sending current message list for sending
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
        $this->games = new App\Games();
    }

    /**
     * **Method** get name of current bot
     * @return string name of current bot
     */
    public function getName() : string
    {
        return $this->className;
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
     * **Method** get status bot proccessing
     * @return bool
     */
    public function isListening() : bool
    {
        return $this->listeningFlag;
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
