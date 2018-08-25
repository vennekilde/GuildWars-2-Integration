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
        $timestamp = time(); 
        StatisticsPersistence::persistStatistic($event->getAvgTimePerKey(), StatisticsPersistence::AVERAGE_TIME_PER_KEY, $timestamp);
        StatisticsPersistence::persistStatistic($event->getFailedSyncs(), StatisticsPersistence::API_ERRORS, $timestamp);
        StatisticsPersistence::persistStatistic($event->getSuccessfulSyncs(), StatisticsPersistence::API_SUCCESS, $timestamp);
        
        $this->collectStatistics();
    }

    public function collectStatistics() {
        $notExpiredPerWorld = StatisticsPersistence::countNotExpiredAPIKeys();
        $expiredKeysPerWorld = StatisticsPersistence::countExpiredAPIKeys();
        $tempAccessPerWorld = StatisticsPersistence::countNotExpiredTemporaryAccess();
        $tempAccessExpriedPerWorld = StatisticsPersistence::countExpiredTemporaryAccess();
        $usersPerService = StatisticsPersistence::countServiceUsers();
        
        $timestamp = time();
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
        
        foreach($usersPerService AS $serviceUserCount){
            StatisticsPersistence::persistStatistic($serviceUserCount["count"], StatisticsPersistence::SERVICE_USER_NUMBERS, $timestamp, $serviceUserCount["service_id"]);
        }
    }
    
    
    /**
     * 
     * @param int[] $statisticTypes
     * @param int $useHoursGranularityAfter
     * @param int $useDaysGranularityAfter
     * @param int $newerThan
     * @param int $sortMethod, 1 == highest, 2 == type
     * @return array
     */
    public function getCombinedChartData($statisticTypes, $useHoursGranularityAfter = null, $useDaysGranularityAfter = null, $newerThan = null, $sortMethod = 1){
        $chartData = array(null);
        $statisticsData = StatisticsPersistence::getStatistics($statisticTypes, $newerThan);
        
        $typeToColumnId = array("x-axis" => 0);
        
        
        //Temporary solution until google chart can sort labels by value
        if($sortMethod == 1){
            $newestStatisticsData = StatisticsPersistence::getNewestStatistics($statisticTypes);
            for($i = 0; $i < count($newestStatisticsData); $i++){
                $newestStatistic = $newestStatisticsData[$i];
                $typeToColumnId[$newestStatistic["type"].":".$newestStatistic["data"]] = $i + 1;
            }
        } else if($sortMethod == 2){
            for($i = 0; $i < count($statisticTypes); $i++){
                $typeToColumnId[strval($statisticTypes[$i]).":0"] = $i + 1;
            }
        } 
        
        $lastRowTimestamp = null;
        $lastRow = null;
        $trimData = isset($useHoursGranularityAfter) || isset($useDaysGranularityAfter);
        for($i = 1; $i < count($statisticsData); $i++){
            $dataPoint = $statisticsData[$i];
            
            $timeStamp = $dataPoint["timestamp"];
            $time = strtotime($timeStamp);
            $currentTime = time();
            if($time >= $currentTime - $useHoursGranularityAfter || !$trimData) {
                $trimmedTimestamp = $timeStamp;
            } else if(isset($useHoursGranularityAfter) && $time >= $currentTime - $useDaysGranularityAfter){
                $roundedTime = ceil($time / 3600) * 3600; //3600 1 hour in seconds
                $trimmedTimestamp = date("Y-m-d H:i:s", $roundedTime);
            } else {
                $roundedTime = ceil($time / 86400) * 86400; //86400 1 day in seconds
                $trimmedTimestamp = date("Y-m-d H:i:s", $roundedTime);
            }
            
            if($lastRowTimestamp != $trimmedTimestamp){
                if($lastRow != null){
                    $chartData[] = $lastRow;
                }
                $lastRow = array_fill(0, count($typeToColumnId), null);
                $lastRow[0] = $trimmedTimestamp;
                $lastRowTimestamp = $trimmedTimestamp;
            }
            $key = $this->getIndexFromChart($typeToColumnId, $dataPoint["type"], intval($dataPoint["data"]));
            $lastRow[$key] = $dataPoint["statistic"];
        }
        $chartData[] = $lastRow;
        
        $this->interpolateNulls($chartData, count($typeToColumnId));
        
        $chartData[0] = array_keys($typeToColumnId);
        
        return $chartData;
    }

    public function getIndexFromChart(&$typeToColumnId, $type, $data){
        $key = "$type:$data";
        $index = -1;
        if(!isset($typeToColumnId[$key])){
            $index = count($typeToColumnId);
            $typeToColumnId[$key] = $index;
        } else {
            $index = $typeToColumnId[$key];
        }
        return $index;
    }
    
    public function interpolateNulls(&$array, $size){
        foreach($array AS $key => $row){
            for($i = 0; $i < $size; $i++){
                if(!isset($row[$i])){
                    $this->interpolateNull($array, $key, $i);
                }
            }
        }
    }
    
    public function interpolateNull(&$array, $rowIndex, $columnIndex, $startPosition = 1){
        for($i = $rowIndex - 1; $i >= $startPosition; $i--){
            if(isset($array[$i][$columnIndex])){
                $array[$rowIndex][$columnIndex] = $array[$i][$columnIndex];
                return;
            }
        }
        
        for($i = $rowIndex + 1; $i < count($array); $i++){
            if(isset($array[$i][$columnIndex])){
                $array[$rowIndex][$columnIndex] = $array[$i][$columnIndex];
                return;
            }
        }
        
    }
    
    public function fillNullWith(&$array, $size, $replacement){
        foreach($array AS $key => $row){
            for($i = 0; $i < $size; $i++){
                if(!isset($row[$i])){
                    $array[$key][$i] = $replacement;
                }
            }
        }
    }
}
