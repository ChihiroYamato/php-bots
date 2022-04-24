<?php

namespace Anet\App\Helpers;

final class TimeTracker
{
    private array $timePoints;

    public function __construct()
    {
        $this->timePoints = ['init' => hrtime(true)];
    }

    public function setPoint(string $point) : void
    {
        $count = 1;
        $newPoint = $point;

        while (isset($this->timePoints[$newPoint])) {
            $newPoint = $point . $count++;
        }

        $this->timePoints[$newPoint] = hrtime(true);
    }

    public function getStatistic() : array
    {
        $count = count($this->timePoints);
        if ($count < 2) {
            return ['init' => 0];
        }

        $keys = array_keys($this->timePoints);
        $values = array_values($this->timePoints);
        $result = [];

        for ($i = 1; $i < $count; $i++) {
            $result[] = ($values[$i] - $values[$i - 1]) / 1000000000;
        }

        array_shift($keys);

        return array_combine($keys, $result);
    }
}
