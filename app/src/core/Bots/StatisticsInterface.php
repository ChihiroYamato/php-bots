<?php

namespace Anet\App\Bots;

/**
 * **StatisticsInterface** -- interface for get statistic
 */
interface StatisticsInterface
{
    /**
     * **Method** get class statistic
     * @return array statistic data
     */
    public function getStatistics() : array;
}
