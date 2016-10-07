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

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\GW2AccountDataExpiredEvent;
use GW2Integration\Events\Events\GW2AccountDataRefreshedEvent;
use GW2Integration\Modules\Verification\VerificationController;
use GW2Integration\Persistence\Persistence;
use PDO;


if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of APIKeyPersistenceHelper
 *
 * @author Jeppe Boysen Vennekilde
 */
class APIKeyPersistenceHelper {
    
    /**
     * 
     * @param type $offset Starting point of the pagination
     * @param type $limit number of entries to retrive
     */
    public static function queryAPIKeys($offset, $limit, $sortByLastAttemptedFetched = true){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'api_keys WHERE last_success > last_attempted_fetch - INTERVAL :expiration SECOND AND api_key_permissions != "' . VerificationController::TEMPORARY_API_KEY_PERMISSIONS . '"';
        $queryParams = array(
            ":expiration" => SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        if($sortByLastAttemptedFetched){
            $preparedQueryString .= ' ORDER BY last_attempted_fetch ASC';
        }
        if(isset($limit)){
            $preparedQueryString .= " LIMIT :limit";
            $queryParams[':limit'] = $limit;
        }
        if(isset($offset)){
            $preparedQueryString .= " OFFSET :offset";
            $queryParams[':offset'] = $offset;
        }
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $apiKeys->execute($queryParams);
        
        return $apiKeys;
    }
    
    public static function getAPIKey($linkedUser, $ignoreExpired = true){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'api_keys WHERE link_id = ? AND api_key_permissions != "' . VerificationController::TEMPORARY_API_KEY_PERMISSIONS . '"';
        
        $queryParams = array(
            $linkId
        );
        
        if($ignoreExpired){
            $preparedQueryString .= " AND last_success > last_attempted_fetch - INTERVAL ? SECOND";
            $queryParams[] = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME);
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($queryParams);
        
        return $preparedStatement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @param array $tokenInfo
     * @return boolean
     */
    public static function persistTokenInfo($linkedUser, $apiKey, $tokenInfo, $checkIfAccessRegained = true){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        if($checkIfAccessRegained){
            $pqs = 'SELECT * FROM '.$gw2i_db_prefix.'api_keys WHERE link_id = ? AND last_success <= last_attempted_fetch - INTERVAL ? SECOND';
            $pq = Persistence::getDBEngine()->prepare($pqs);
            $prevAPIData = $pq->execute(array(
                $linkId,
                SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
            ));
            
            if($pq->rowCount() > 0){
                $event = new GW2AccountDataRefreshedEvent($linkId);
                EventManager::fireEvent($event);
            }
        }
        
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'api_keys (link_id, api_key, api_key_name, api_key_permissions)
                VALUES(:link_id, :api_key, :api_key_name, :api_key_permissions)
            ON DUPLICATE KEY UPDATE 
                api_key = VALUES(api_key),
                api_key_name = VALUES(api_key_name),
                api_key_permissions = VALUES(api_key_permissions)';
        $queryParams = array(
            ":link_id" => $linkId,
            ":api_key" => $apiKey,
            ":api_key_name" => $tokenInfo["name"],
            ":api_key_permissions" => implode(",", $tokenInfo["permissions"])
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        return $preparedStatement->execute($queryParams);
    }

    /**
     * Update an api keys last attempted fetch time to the current timestamp
     * @param type $apiKey
     * @param type $unixTimestamp
     */
    public static function updateLastAPIKeyAttemptedFetch($apiKey, $unixTimestamp = null, $checkIfExpired = true){
        global $gw2i_db_prefix;
        
        if(!isset($unixTimestamp)){
            $unixTimestamp = time();
        }
        $timestamp = date('Y-m-d G:i:s', $unixTimestamp);
        
        $queryParams = array(
            $apiKey
        );
        if(isset($timestamp)){
            $setQueryPlaceholder = '?';
            array_unshift($queryParams, $timestamp);
        } else {
            $setQueryPlaceholder = 'CURRENT_TIMESTAMP';
        }
        $preparedQueryString = 'UPDATE '.$gw2i_db_prefix.'api_keys SET last_attempted_fetch = '.$setQueryPlaceholder.' WHERE api_key = ?';
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $result = $preparedStatement->execute($queryParams);
        
        if($checkIfExpired){
            $pqs = 'SELECT * FROM '.$gw2i_db_prefix.'api_keys WHERE api_key = ? AND last_success <= last_attempted_fetch - INTERVAL ? SECOND';
            $pq = Persistence::getDBEngine()->prepare($pqs);
            $expiredData = $pq->execute(array(
                $apiKey,
                SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
            ));
            
            if($pq->rowCount() > 0){
                $event = new GW2AccountDataExpiredEvent($expiredData["link_id"]);
                EventManager::fireEvent($event);
            }
        }
        
        return $result;
    }

    /**
     * Update an api keys last successful fetch time to the current timestamp'
     * This also updates last attempted fetch in the process
     * @param type $apiKey
     * @param type $unixTimestamp
     */
    public static function updateLastAPIKeySuccessfulFetch($apiKey, $unixTimestamp = null){
        global $gw2i_db_prefix;
        if(!isset($unixTimestamp)){
            $unixTimestamp = time();
        }
        $timestamp = date('Y-m-d G:i:s', $unixTimestamp);
        $queryParams = array(
            $apiKey
        );
        if(isset($timestamp)){
            $setQueryPlaceholder = '?';
            array_unshift($queryParams, $timestamp, $timestamp);
        } else {
            $setQueryPlaceholder = 'CURRENT_TIMESTAMP';
        }
        
        $preparedQueryString = 'UPDATE '.$gw2i_db_prefix.'api_keys SET last_attempted_fetch = '.$setQueryPlaceholder.', last_success = '.$setQueryPlaceholder.' WHERE api_key = ?';
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        return $preparedStatement->execute($queryParams);
    }
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @return type
     */
    public static function getExpiredAPIKeys(){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'api_keys WHERE last_success <= last_attempted_fetch - INTERVAL ? SECOND';
        $queryParams = array(
            SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME)
        );
        
        $apiKeys = Persistence::getDBEngine()->prepare($preparedQueryString);

        $apiKeys->execute($queryParams);
        
        return $apiKeys;
    }
    
    public static function cleanupDatabase(){
        //Currently takes too long to execute
//        $queries = array(
//            "DELETE k FROM gw2integration_api_keys k LEFT JOIN gw2integration_user_service_links l ON k.link_id = l.link_id WHERE l.link_id IS NULL",
//            "DELETE a FROM gw2integration_accounts a LEFT JOIN gw2integration_user_service_links l ON a.link_id = l.link_id WHERE l.link_id IS NULL",
//        );
//        foreach($queries AS $query){
//            Persistence::getDBEngine()->exec($query);
//        }
    }
}
