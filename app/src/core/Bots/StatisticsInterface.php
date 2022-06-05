<?php

namespace Anet\App\Bots;

/**
 * **StatisticsInterface** -- interface for get statistic
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
interface StatisticsInterface
{
    /**
     * **Method** get class statistic
     * @return string[] statistic data
     */
    public function getStatistics() : array;
}
