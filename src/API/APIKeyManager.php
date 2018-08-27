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

use GW2Integration\Controller\GW2DataController;
use GW2Integration\Controller\GW2TokenInfoController;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\Exceptions\InvalidAPIKeyFormatException;
use GW2Integration\Exceptions\InvalidAPIKeyNameException;
use GW2Integration\Exceptions\MissingRequiredAPIKeyPermissions;
use GW2Integration\Exceptions\RequirementsNotMetException;
use GW2Integration\Persistence\Helper\APIKeyPersistenceHelper;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\Utils\HashingUtils;
use GW2Treasures\GW2Api\GW2Api;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * Description of APIKeyManager
 *
 * @author Jeppe Boysen Vennekilde
 */
class APIKeyManager {
    /**
     *
     * @var string 
     */
    const API_KEY_REGEX = "^[a-zA-Z0-9\-]{72}$^";
    
    /**
     *
     * @var GW2Api 
     */
    private static $api;
    static function init(){
        static::$api = new GW2Api();
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @param boolean $ignoreAPIKeyFormat
     * @param boolean $ignoreAPIKeyName
     * @param boolean $ignoreAPIKeyPermissions
     * @param boolean $ignoreCharacterLevelRequirement
     * @throws InvalidAPIKeyFormatException
     * @throws MissingRequiredAPIKeyPermissions
     */
    public static function addAPIKeyForUser($linkedUser, $apiKey, $ignoreAPIKeyFormat = false, $ignoreAPIKeyName = false, $ignoreAPIKeyPermissions = false, $ignoreCharacterLevelRequirement = false){
        global $logger;
        $timeStarted = microtime(true) * 1000; 
        //Throws exceptions if not valid'
        $tokenInfo = static::isAPIKeyValid($linkedUser, $apiKey, $ignoreAPIKeyFormat, $ignoreAPIKeyName);
        
        $hasRequiredPermissions = static::hasRequiredPermission($tokenInfo["permissions"]);
        if(!$ignoreAPIKeyPermissions){
            if($hasRequiredPermissions !== true){
                throw new MissingRequiredAPIKeyPermissions($hasRequiredPermissions);
            }
        }
        
        if(!$ignoreCharacterLevelRequirement){
            //Check level requirement
            $level_restriction = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::ACCOUNT_LEVEL_REQUIREMENT);
            $charactersData = null;
            if($level_restriction > 0 && $hasRequiredPermissions === true){
                $charactersData = (array)static::$api->characters($apiKey)->all();
                static::hasCharacterInRequiredLevel($charactersData);
            }
        }
        //Get data from Account endpoint
        $accountData = (array)static::$api->account($apiKey)->get();
        
        
        
        //Account data
        $success1 = GW2DataController::resyncAccountEndpoint($linkedUser, $apiKey, true, $accountData);
        //Token info
        $success2 = GW2TokenInfoController::processTokenInfo($linkedUser, $apiKey, $tokenInfo);
        
        if($success2){
            $logger->info("Set API Key for ".$linkedUser->compactString()." to $apiKey");
        }
        
        LinkingPersistencyHelper::persistUserServiceLinks($linkedUser);
        
        if($success1 || $success2){
            APIKeyPersistenceHelper::updateLastAPIKeySuccessfulFetch($apiKey);
        } else {
            APIKeyPersistenceHelper::updateLastAPIKeyAttemptedFetch($apiKey);
        }
        $permissions = $tokenInfo["permissions"];

        //Don't have to request account data again
        $index = array_search("account",$permissions);
        if($index!==false){
            array_splice($permissions, $index, 1);
        }

        //Request data for the rest of the endpoints
        APIKeyProcessor::resyncAPIKey($linkedUser, $apiKey, $permissions, false);
        
        
        $timeEnded = microtime(true) * 1000; 
        EventManager::fireEvent(new APISyncCompleted(1, 1, $timeStarted, $timeEnded));
    }
    
    public static function getRequiredAPIKeyPermissions(){
        $requiredPermission = array("account");
        
        if(SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::ACCOUNT_LEVEL_REQUIREMENT) > 0){
            $requiredPermission[] = "characters";
        }
        
        return $requiredPermission;
    }
    
    public static function hasRequiredPermission($givenPermissions){
        $requiredPermissions = static::getRequiredAPIKeyPermissions();
        $missingPermissions = array_diff($requiredPermissions, $givenPermissions);
        if(sizeof($missingPermissions) == 0){
            return true;
        }
        return $missingPermissions;
    }
    
    
    public static function hasCharacterInRequiredLevel($charactersData){
        $level_restriction = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::ACCOUNT_LEVEL_REQUIREMENT);
        if($level_restriction > 0){
            $passed = false;
            $highestLevel = 0;
            foreach($charactersData AS $character){
                $characterArray = (array)$character;
                $characterLevel = $characterArray["level"];
                if($characterLevel >= $level_restriction){
                    $passed = true;
                    break;
                }
                if($characterLevel > $highestLevel){
                    $highestLevel = $characterLevel;
                }
            }
            if(!$passed){
                throw new RequirementsNotMetException("Requirement not met", "level ".$level_restriction, $highestLevel);
            }
        }
        return true;
    }
    
    /**
     * 
     * @param type $apiKey
     * @throws InvalidAPIKeyFormatException
     */
    public static function isAPIKeyFormatValid($apiKey){
        //Check if API key is at least correctly formatted
        $regexMatch = preg_match(self::API_KEY_REGEX, $apiKey);
        if($regexMatch === 0){
            throw new InvalidAPIKeyFormatException();
        }
        
        return true;
    }
    
    /**
     * 
     * @param type $linkedUser
     * @param type $apiKey
     * @param type $ignoreAPIKeyFormat
     * @param type $ignoreAPIKeyName
     * @return type
     * @throws InvalidAPIKeyNameException
     */
    public static function isAPIKeyValid($linkedUser, $apiKey, $ignoreAPIKeyFormat = false, $ignoreAPIKeyName = false){
        if(!$ignoreAPIKeyFormat){
            //Throws InvalidAPIKeyFormatException if not valid
            static::isAPIKeyFormatValid($apiKey);
        }
        //Will throw exceptions if not able to retrieve token info
        $tokenInfo = (array)static::$api->tokeninfo($apiKey)->get();
        
        if(!$ignoreAPIKeyName){
            if(SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::STRICT_API_KEY_NAME) != "0"){
                $apiKeyName = str_replace(" ", "", $tokenInfo["name"]);
                $validKeyNames = static::getAPIKeyNamesForUser($linkedUser);
                $isValid = false;
                foreach($validKeyNames AS $validKeyName){
                    if(strcasecmp($apiKeyName, $validKeyName) === 0) {
                        $isValid = true;
                        break;
                    }
                }
                if(!$isValid){
                    throw new InvalidAPIKeyNameException($validKeyNames[0], $apiKeyName);
                }
            }
        }
        
        return $tokenInfo;
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param integer $userType 0 = Forum User, 1 = Teamspeak User
     */
    public static function getAPIKeyNamesForUser($linkedUser){
        $validKeyNames = array();
        foreach($linkedUser->getPrimaryUserServiceLinks() AS $userServiceLink){
            $userId = $userServiceLink->getServiceUserId();
            if($userServiceLink->getServiceId() != 0){
                $preId = $userServiceLink->getServiceId() . "-";
            } else {
                $preId = "";
            }
            $validKeyNames[] = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_NAME_PREFIX) . $preId .  strtoupper(HashingUtils::generateWeakHash($userId));
        }
        
        if($userId === null){
            $userId = $linkedUser->getLinkedId();
        }
        return $validKeyNames;
    }
    
    /**
     * Calculate the amount of time in seconds left before an API Key expires, given its last
     * successful fetch
     * @global type $expiration_grace_periode
     * @param type $timestamp
     * @return type
     */
    public static function calculateTimeLeftBeforeKeyExpires($lastSuccess) {
        return strtotime($lastSuccess) + SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME) - time();
    }
    
    public static function analyzeAnetAPI(){
        $apiKey = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::DEBUG_API_KEY);
        try{
            if(!empty($apiKey) && static::isAPIKeyFormatValid($apiKey)){
                static::$api->tokeninfo($apiKey);
                static::$api->account($apiKey);
                static::$api->characters($apiKey);
            }
            SettingsPersistencyHelper::persistSetting(SettingsPersistencyHelper::IS_API_DOWN, 0);
        } catch(Exception $e){
            SettingsPersistencyHelper::persistSetting(SettingsPersistencyHelper::IS_API_DOWN, 1);
        }
    }
}
APIKeyManager::init();