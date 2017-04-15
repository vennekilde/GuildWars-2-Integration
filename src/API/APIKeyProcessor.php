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

namespace GW2Integration\API;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GW2Integration\Controller\GW2DataController;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\Persistence\Helper\APIKeyPersistenceHelper;
use GW2Treasures\GW2Api\GW2Api;
use GW2Treasures\GW2Api\V2\Authentication\Exception\AuthenticationException;


if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of APIKeyProcessor
 *
 * @author Jeppe Boysen Vennekilde
 */
class APIKeyProcessor {
    /**
     *
     * @var GW2Api 
     */
    private static $api;
    static function init(){
        static::$api = new GW2Api();
    }
    
    public static function resyncLinkedUser($linkedUser, $fireAPISyncCompletedEvent = true){
        $apiKeyData = APIKeyPersistenceHelper::getAPIKey($linkedUser);
        return static::resyncAPIKey($linkedUser, $apiKeyData["api_key"], explode(",", $apiKeyData["api_key_permissions"]), $fireAPISyncCompletedEvent);
    }
    
    /**
     * Resync an API Key for a given LinkedUser with the guild wars 2 API for all
     * supported/permitted endpoints
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @param array $permissions
     */
    public static function resyncAPIKey($linkedUser, $apiKey, $permissions, $fireAPISyncCompletedEvent = true){
        global $logger;
        $fireAPISyncCompletedEvent = $fireAPISyncCompletedEvent && !empty($permissions);
        //True if successful in fetching updated data from the api
        $success = false;
        try {
            if($fireAPISyncCompletedEvent){
                //Time started in MS
                $timeStarted = microtime(true) * 1000; 
            }
            foreach($permissions as $permission){
                $result = false;
                try{
                    switch($permission){
                        case "account":
                            $result = GW2DataController::resyncAccountEndpoint($linkedUser, $apiKey);
                            break;
                        case "guilds":
                            $result = GW2DataController::resyncGuildLeaderMembersEndpoint($linkedUser, $apiKey);
                            break;
                    }
                    switch($permission){
                        case "characters":
                            $result = GW2DataController::updateCharacters($linkedUser, $apiKey);
                            break;
                    }

                    if($result){
                        $success = true;
                    }
                } catch(Exception $e){
                    $base_msg = "Could not sync API Key \"$apiKey\" for user ".$linkedUser->compactString()." - ";
                    if($e instanceof AuthenticationException || $e instanceof ConnectException){
                        $logger->error($base_msg . get_class($e) . ": " . $e->getMessage());
                    } else {
                        $logger->error($base_msg . get_class($e) . ": " . $e->getMessage(), $e->getTrace());
                    }
                }
            }
            
            //Update if we where successful with fetching data from the api, or if we just attempted (pat on the shoulder, we tried ^^)
            if($success){
                APIKeyPersistenceHelper::updateLastAPIKeySuccessfulFetch($apiKey);
            } else {
                APIKeyPersistenceHelper::updateLastAPIKeyAttemptedFetch($apiKey);
            }
            if($fireAPISyncCompletedEvent){
                //Time ended in MS
                $timeEnded = microtime(true) * 1000; 
                EventManager::fireEvent(new APISyncCompleted(1, ($success ? 1 : 0), $timeStarted, $timeEnded));
            }
        } catch(Exception $e){
            APIKeyPersistenceHelper::updateLastAPIKeyAttemptedFetch($apiKey);
            $logger->error(get_class($e) . ": " . $e->getMessage(), $e->getTrace());
            throw $e;
        }
        return $success;
    }
}
APIKeyProcessor::init();
