<?php

namespace App\Anet\Helpers;

final class TimeTracker
{
    private int $systemTimeInit;
    private \DateTime $DateTimeInit;
    private array $points;
    private array $bufferPoints;
    private array $tracker;
    private ?float $minIteration;
    private ?float $maxIteration;

    public function __construct()
    {
        $this->systemTimeInit = hrtime(true);
        $this->DateTimeInit = new \DateTime();
        $this->points = [];
        $this->bufferPoints = [];
        $this->tracker = [];
        $this->minIteration = null;
        $this->maxIteration = null;
    }

    public function getTimeInit(string $format = 'H:i:s'): string
    {
        return $this->DateTimeInit->format($format);
    }

    public function getDuration(string $format = '%H:%I:%S'): string
    {
        return $this->DateTimeInit->diff(new \DateTime())->format($format);
    }

    public function trackerStart(string $trackName) : void
    {
        $this->tracker[$trackName] = hrtime(true);
    }

    public function trackerStop(string $trackName) : void
    {
        $this->tracker[$trackName] = null;
    }

    public function trackerState(string $trackName) : bool
    {
        return isset($this->tracker[$trackName]);
    }

    public function trackerCheck(string $trackName, int $timer) : bool
    {
        if (! $this->trackerState($trackName)) {
            return false;
        }

        return (((hrtime(true) - $this->tracker[$trackName]) / 1000000000) > $timer);
    }

    public function startPointTracking() : void
    {
        $this->bufferPoints = ['__START__' => hrtime(true)];
    }

    public function setPoint(string $point) : void
    {
        $this->bufferPoints[$point] = hrtime(true);
    }

    public function finishPointTracking() : void
    {
        $this->points[] = $this->bufferPoints;
        $this->bufferPoints = [];
    }

    public function clearPoints() : void
    {
        $this->points = [];
    }

    public function sumPointsAverage() : float
    {
        $iterations = $this->fetchIterations();

        if (empty($iterations)) {
            return 0;
        }

        return array_sum($iterations) / (count($this->points) * 1000000000);
    }

    public function fetchMinIteration() : float
    {
        $iterations = $this->fetchIterations();

        if (empty($iterations)) {
            return (float) $this->minIteration;
        }

        $min = min($iterations);

        if ($this->minIteration === null || $min < $this->minIteration) {
            $this->minIteration = $min;
        }

        return $this->minIteration;
    }
    public function fetchMaxIteration() : float
    {
        $iterations = $this->fetchIterations();

        if (empty($iterations)) {
            return (float) $this->maxIteration;
        }

        $max = max($iterations);

        if ($this->maxIteration === null || $max > $this->maxIteration) {
            $this->maxIteration = $max;
        }

        return $this->maxIteration;
    }

    private function fetchIterations() : array
    {
        if (empty($this->points)) {
            return [];
        }

        $list = $this->points;

        return array_map(fn (array $tem) => array_pop($tem) - array_shift($tem), $list);
    }

    public static function calculateDuration(int $baseTime) : int
    {
        return (int) ((hrtime(true) - $baseTime) / 1000000000);
    }
}
