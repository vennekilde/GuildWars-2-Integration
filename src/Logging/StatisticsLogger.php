<?php

/*
 * The MIT License
 *
 * Copyright 2016 Jeppe Boysen Vennekilde.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace GW2Integration\Logger;

use GW2Integration\Events\EventListener;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\Persistence\Helper\StatisticsPersistence;

/**
 * Description of Statistics
 *
 * @author Jeppe Boysen Vennekilde
 */
class StatisticsLogger implements EventListener{
    //put your code here
    public function __construct() {
        EventManager::registerListener($this);
    }
    
    public function onAPISyncCompleted(APISyncCompleted $event){
        $timestamp = strtotime(time()); 
        StatisticsPersistence::persistStatistic($event->getAvgTimePerKey(), StatisticsPersistence::AVERAGE_TIME_PER_KEY, $timestamp);
        StatisticsPersistence::persistStatistic($event->getFailedSyncs(), StatisticsPersistence::API_ERRORS, $timestamp);
        
        $this->collectStatistics();
    }

    public function collectStatistics() {
        $notExpiredPerWorld = StatisticsPersistence::countNotExpiredAPIKeys();
        $expiredKeysPerWorld = StatisticsPersistence::countExpiredAPIKeys();
        $tempAccessPerWorld = StatisticsPersistence::countNotExpiredTemporaryAccess();
        $tempAccessExpriedPerWorld = StatisticsPersistence::countExpiredTemporaryAccess();
        
        $timestamp = strtotime(time());
        foreach($notExpiredPerWorld AS $notExpired){
            StatisticsPersistence::persistStatistic($notExpired["count"], StatisticsPersistence::VALID_KEYS, $timestamp, $notExpired["a_world"]);
        }
        
        foreach($expiredKeysPerWorld AS $expiredKeys){
            StatisticsPersistence::persistStatistic($expiredKeys["count"], StatisticsPersistence::EXPIRED_KEYS, $timestamp, $expiredKeys["a_world"]);
        }
        
        foreach($tempAccessPerWorld AS $tempAccess){
            StatisticsPersistence::persistStatistic($tempAccess["count"], StatisticsPersistence::TEMPORARY_ACCESS, $timestamp, $tempAccess["a_world"]);
        }
        
        foreach($tempAccessExpriedPerWorld AS $tempAccessExpried){
            StatisticsPersistence::persistStatistic($tempAccessExpried["count"], StatisticsPersistence::TEMPORARY_ACCESS_EXPIRED, $timestamp, $tempAccessExpried["a_world"]);
        }
    }
    
    /**
     * 
     * @param int[] $statisticTypes
     */
    public function getCombinedChartData(...$statisticTypes){
        $statisticsData = StatisticsPersistence::getStatistic($statisticTypes);
    }

}
