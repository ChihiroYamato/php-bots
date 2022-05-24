<?php

namespace App\Anet\Helpers;

/**
 * **TimerTracker** -- helper class with methods for setup and calulate timers
 */
final class TimeTracker
{
    /**
     * @var \DateTime $DateTimeInit `private` time stamp of init TimeTracker object
     */
    private \DateTime $DateTimeInit;
    /**
     * @var array $points `private` local storage of tracker points
     */
    private array $points;
    /**
     * @var array $bufferPoints `private` storage of current tracker block
     */
    private array $bufferPoints;
    /**
     * @var array $tracker `private` storage of named timer
     */
    private array $tracker;
    /**
     * @var null|float $minIteration `private` value of min tracker points iteration
     */
    private ?float $minIteration;
    /**
     * @var null|float $maxIteration `private` value of max tracker points iteration
     */
    private ?float $maxIteration;

    /**
     * TimeTracker Initialization
     * @return void
     */
    public function __construct()
    {
        $this->DateTimeInit = new \DateTime();
        $this->points = [];
        $this->bufferPoints = [];
        $this->tracker = [];
        $this->minIteration = null;
        $this->maxIteration = null;
    }

    /**
     * **Method** return time stamp of initializing **TimeTracker** object
     * @param string $format `[optional]` string format of returning time stamp
     * @return string time stamp of initializing TimeTracker object
     */
    public function getTimeInit(string $format = 'Y-m-d H:i:s') : string
    {
        return $this->DateTimeInit->format($format);
    }

    /**
     * **Method** return difference between current time and specified time stamp
     * @param int $baseTime specified time stamp in seconds
     * @return int difference between current and specified stamp in seconds
     */
    public static function getDurationFromStamp(int $baseTime) : int
    {
        return (int) ((hrtime(true) - $baseTime) / 1000000000);
    }

    /**
     * **Method** return difference between time stamp of initializing **TimeTracker** object and current time
     * @param string $format `[optional]` string format of returning time stamp
     * @return string difference between init and current time
     */
    public function getDuration(string $format = '%H:%I:%S') : string
    {
        return $this->DateTimeInit->diff(new \DateTime())->format($format);
    }

    /**
     * **Method** initialize timer
     * @param string $trackName name of timer
     * @return void
     */
    public function trackerStart(string $trackName) : void
    {
        $this->tracker[$trackName] = hrtime(true);
    }

    /**
     * **Method** stops timer by name
     * @param string $trackerName name of timer
     * @return void
     */
    public function trackerStop(string $trackName) : void
    {
        $this->tracker[$trackName] = null;
    }

    /**
     * **Method** checks existing of timer by name
     * @param string $trackName name of timer
     * @return bool return true if timer exists else false
     */
    public function trackerState(string $trackName) : bool
    {
        return isset($this->tracker[$trackName]);
    }

    /**
     * **Method** checks expiration of timer by name
     * @param string $trackName name of timer
     * @param int $timer time in seconds which define expiration of timer
     * @return bool return true if timer is already expied else - false
     */
    public function trackerCheck(string $trackName, int $timer) : bool
    {
        if (! $this->trackerState($trackName)) {
            return false;
        }

        return (((hrtime(true) - $this->tracker[$trackName]) / 1000000000) > $timer);
    }

    /**
     * **Method** initialize tracking block
     * @return void
     */
    public function startPointTracking() : void
    {
        $this->bufferPoints = ['__START__' => hrtime(true)];
    }

    /**
     * **Method** add tracker point to tracking block
     * @param string $point name of tracker point
     */
    public function setPoint(string $point) : void
    {
        $this->bufferPoints[$point] = hrtime(true);
    }

    /**
     * **Method** save & refresh tracking block to local points storage
     * @return void
     */
    public function finishPointTracking() : void
    {
        $this->points[] = $this->bufferPoints;
        $this->bufferPoints = [];
    }

    /**
     * **Method** refresh local points storage
     * @return void
     */
    public function clearPoints() : void
    {
        $this->points = [];
    }

    /**
     * **Method** calculate average value of points iterations from local points storage
     * @return float average value of points
     */
    public function sumPointsAverage() : float
    {
        $iterations = $this->fetchIterations();

        if (empty($iterations)) {
            return 0;
        }

        return array_sum($iterations) / (count($this->points) * 1000000000);
    }

    /**
     * **Method** calculate and save min iteration of points from local points storage
     * @return float min iteration of points
     */
    public function fetchMinIteration() : float
    {
        $iterations = $this->fetchIterations();

        if (empty($iterations)) {
            return (float) $this->minIteration;
        }

        $min = min($iterations) / 1000000000;

        if ($this->minIteration === null || $min < $this->minIteration) {
            $this->minIteration = $min;
        }

        return $this->minIteration;
    }

    /**
     * **Method** calculate and save max iteration of points from local points storage
     * @return float max iteration of points
     */
    public function fetchMaxIteration() : float
    {
        $iterations = $this->fetchIterations();

        if (empty($iterations)) {
            return (float) $this->maxIteration;
        }

        $max = max($iterations) / 1000000000;

        if ($this->maxIteration === null || $max > $this->maxIteration) {
            $this->maxIteration = $max;
        }

        return $this->maxIteration;
    }

    /**
     * **Method** calulate value of iterations from local points storage
     * @return array list of all iterations
     */
    private function fetchIterations() : array
    {
        if (empty($this->points)) {
            return [];
        }

        $list = $this->points;

        return array_map(fn (array $tem) => array_pop($tem) - array_shift($tem), $list);
    }
}
