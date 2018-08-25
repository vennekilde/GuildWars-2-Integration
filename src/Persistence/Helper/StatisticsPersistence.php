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

namespace GW2Integration\Persistence\Helper;

use GW2Integration\Modules\Verification\VerificationController;
use GW2Integration\Persistence\Persistence;
use PDO;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of StatisticsPersistenceHelpter
 *
 * @author Jeppe Boysen Vennekilde
 */
class StatisticsPersistence {
    
    const API_ERRORS = 2;
    const AVERAGE_TIME_PER_KEY = 3;
    const VALID_KEYS = 4;
    const EXPIRED_KEYS = 5;
    const TEMPORARY_ACCESS = 6;
    const TEMPORARY_ACCESS_EXPIRED = 7;
    const API_SUCCESS = 8;
    const SERVICE_USER_NUMBERS = 9;
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param int $statistic
     * @param int $type
     * @param type $timestamp
     * @param int $data
     * @return boolean
     */
    public static function persistStatistic($statistic, $type, $timestamp, $data = null){
        global $gw2i_db_prefix;
        
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'statistics (statistic, type, timestamp'.(isset($data) ? ", data" : "").')
                VALUES(?, ?, FROM_UNIXTIME(?)'.(isset($data) ? ", ?" : "").')';
        $queryParams = array(
            $statistic,
            $type,
            $timestamp
        );
        if(isset($data)){
            $queryParams[] = $data;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $result = $preparedStatement->execute($queryParams);
        
        $lastInsertedId = Persistence::getDBEngine()->lastInsertId();
        static::cleanStatisticValue($statistic, $lastInsertedId, $type, $data);
        
        return $result;
    }
    
    
    public static function cleanStatisticValue($statistic, $rid, $type, $data = null){
        global $gw2i_db_prefix;
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'statistics WHERE rid < ? AND type = ?'.(isset($data) ? " AND data = ?" : "").' ORDER BY timestamp DESC LIMIT 2';
        
        $queryParams = array(
            $rid,
            $type
        );
        if(isset($data)){
            $queryParams[] = $data;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $preparedStatement->execute($queryParams);
        
        $prevTwoRows = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        if(count($prevTwoRows) == 2){
            if($prevTwoRows[0]["statistic"] == $statistic && $prevTwoRows[1]["statistic"] == $statistic){
                static::deleteStatistic($prevTwoRows[0]["rid"]);
            }
        }
    }
    
    public static function deleteStatistic($rid){
        global $gw2i_db_prefix;
        $preparedQueryString = 'DELETE FROM '.$gw2i_db_prefix.'statistics WHERE rid = ?';
        
        $queryParams = array(
            $rid
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $preparedStatement->execute($queryParams);
    }
    
    
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param int[] $types
     * @param int $newerThan in seconds
     * @return array
     */
    public static function getStatistics($types, $newerThan = null){
        global $gw2i_db_prefix;
        
        $inQuery = implode(',', array_fill(0, count($types), '?'));
        $preparedQueryString = '
            SELECT * FROM '.$gw2i_db_prefix.'statistics 
                WHERE type IN('.$inQuery.') '.(isset($newerThan) ? "AND timestamp >= NOW() - INTERVAL ? SECOND" : "").' ORDER BY timestamp ASC, rid ASC';
        $queryParams = $types;
        
        if(isset($newerThan)){
            $queryParams[] = $newerThan;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $preparedStatement->execute($queryParams);
        
        return $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getNewestStatistics($types){
        global $gw2i_db_prefix;
        
        $inQuery = implode(',', array_fill(0, count($types), '?'));
        $preparedQueryString = '
            SELECT t1.* FROM '.$gw2i_db_prefix.'statistics t1
            JOIN (SELECT data, MAX(timestamp) as max_timestamp FROM '.$gw2i_db_prefix.'statistics GROUP BY data) t2
                ON t1.data = t2.data AND t1.timestamp = t2.max_timestamp
            WHERE type IN('.$inQuery.')
            ORDER BY t1.statistic DESC';
        $queryParams = $types;
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $preparedStatement->execute($queryParams);
        
        return $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function countServiceUsers(){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT l.service_id, COUNT(*) as count FROM '.$gw2i_db_prefix.'api_keys k INNER JOIN '.$gw2i_db_prefix.'accounts a ON k.link_id = a.link_id INNER JOIN '.$gw2i_db_prefix.'user_service_links l ON a.link_id = l.link_id WHERE last_success > last_attempted_fetch - INTERVAL ? SECOND AND l.is_primary = 1 GROUP BY l.service_id';
        $queryParams = array(
            SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);

        $apiKeys->execute($queryParams);
        
        return $apiKeys->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function countExpiredAPIKeys(){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT a_world, COUNT(*) as count FROM '.$gw2i_db_prefix.'api_keys k INNER JOIN '.$gw2i_db_prefix.'accounts a ON k.link_id = a.link_id WHERE last_success <= last_attempted_fetch - INTERVAL ? SECOND AND api_key_permissions != "' . VerificationController::TEMPORARY_API_KEY_PERMISSIONS . '" GROUP BY a.a_world';
        $queryParams = array(
            SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);

        $apiKeys->execute($queryParams);
        
        return $apiKeys->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function countExpiredTemporaryAccess(){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT a_world, COUNT(*) as count FROM '.$gw2i_db_prefix.'api_keys k INNER JOIN gw2integration_accounts a ON k.link_id = a.link_id WHERE last_success <= last_attempted_fetch - INTERVAL ? SECOND AND api_key_permissions = "' . VerificationController::TEMPORARY_API_KEY_PERMISSIONS . '" GROUP BY a.a_world';
        $queryParams = array(
            SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);

        $apiKeys->execute($queryParams);
        
        return $apiKeys->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function countNotExpiredAPIKeys(){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT a_world, COUNT(*) as count FROM '.$gw2i_db_prefix.'api_keys k INNER JOIN gw2integration_accounts a ON k.link_id = a.link_id WHERE last_success > last_attempted_fetch - INTERVAL ? SECOND AND api_key_permissions != "' . VerificationController::TEMPORARY_API_KEY_PERMISSIONS . '" GROUP BY a.a_world';
        $queryParams = array(
            SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);

        $apiKeys->execute($queryParams);
        
        return $apiKeys->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function countNotExpiredTemporaryAccess(){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT a_world, COUNT(*) as count FROM '.$gw2i_db_prefix.'api_keys k INNER JOIN gw2integration_accounts a ON k.link_id = a.link_id WHERE last_success > last_attempted_fetch - INTERVAL ? SECOND AND api_key_permissions = "' . VerificationController::TEMPORARY_API_KEY_PERMISSIONS . '"  GROUP BY a.a_world';
        $queryParams = array(
            SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);

        $apiKeys->execute($queryParams);
        
        return $apiKeys->fetchAll(PDO::FETCH_ASSOC);
    }
}
