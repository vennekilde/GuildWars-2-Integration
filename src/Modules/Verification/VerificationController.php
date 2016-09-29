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

namespace GW2Integration\Modules\Verification;

use DateTime;
use GW2Integration\API\APIKeyManager;
use GW2Integration\Controller\LinkedUserController;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Helper\APIKeyPersistenceHelper;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * 
 */
class VerificationController {

    const TEMPORARY_API_KEY_NAME = "temporary";
    const TEMPORARY_API_KEY_PERMISSIONS = "none";
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param Integer $world
     */
    public static function grantTemporaryAccess($linkedUser, $world) {
        global $logger;
        try{
            $accountData = GW2DataPersistence::getExtensiveAccountData($linkedUser);
        } catch (UnableToDetermineLinkId $ex){
            $accountData = false;
        }
        $accessStatus = VerificationController::getVerificationStatusFromAccountData($linkedUser, $accountData, false);

        //Make sure data was actually provided
        if ($accountData !== false && $accountData !== null) {

            switch ($accessStatus->getValue()) {
                //Do not give temp access if either is true
                case VerificationStatus::ACCESS_GRANTED_HOME_WORLD:
                case VerificationStatus::ACCESS_GRANTED_HOME_WORLD_TEMPORARY:
                case VerificationStatus::ACCESS_GRANTED_LINKED_WORLD:
                case VerificationStatus::ACCESS_GRANTED_LIMKED_WORLD_TEMPORARY:
                case VerificationStatus::ACCESS_DENIED_INVALID_WORLD:
                case VerificationStatus::ACCESS_DENIED_BANNED:
                    break;
            }
        }
        if (!isset($accountData["id"])) {
            $accountData["id"] = "temporary-".$linkedUser->fetchServiceUserId."-".$linkedUser->fetchServiceId;
        }
        if (!isset($accountData["name"])) {
            $accountData["name"] = "temporary-".$linkedUser->fetchServiceUserId."-".$linkedUser->fetchServiceId;
        }
        $accountData["world"] = $world;

        if (!isset($accountData["created"])) {
            $date = new DateTime();
            $accountData["created"] = $date->getTimestamp();
        }
        if (!isset($accountData["access"])) {
            $accountData["access"] = -1;
        }
        if (!isset($accountData["commander"])) {
            $accountData["commander"] = 0;
        }

        GW2DataPersistence::persistAccountData($linkedUser, $accountData, true);

        $fakeAPIKey = "temporary-".$linkedUser->fetchServiceUserId."-".$linkedUser->fetchServiceId;
        $tokenInfo = array(
            "name" => self::TEMPORARY_API_KEY_NAME,
            "permissions" => array(self::TEMPORARY_API_KEY_PERMISSIONS)
        );
        APIKeyPersistenceHelper::persistTokenInfo($linkedUser, $fakeAPIKey, $tokenInfo);
        $unixTimestamp = time() + SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::TEMPORARY_ACCESS_EXPIRATION) - SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEY_EXPIRATION_TIME);
        APIKeyPersistenceHelper::updateLastAPIKeySuccessfulFetch($fakeAPIKey, $unixTimestamp);
        
        $linkId = $linkedUser->getLinkedId();
        LinkingPersistencyHelper::persistUserServiceLink(
                new GW2Integration\Entity\UserServiceLink(
                    $linkedUser->fetchServiceId, 
                    $linkedUser->fetchServiceUserId,  
                    true,
                    $linkedUser->fetchServiceDisplayName, 
                    null,
                    (isset($linkId) ? $linkId : null)
                )
            );
        
        LinkingPersistencyHelper::removeAttributeFromAllUserLinks($linkedUser, "tempExpired");
        
        $logger->info($linkedUser->compactString() . " Has been granted temporary access as world $world");
    }

    /**
     * 
     * @global type $gw2i_home_world
     * @global type $gw2i_linked_worlds
     * @param LinkedUser $linkedUser
     * @param type $extensiveAccountData
     * @param boolean $fetchServiceLinks
     * @return VerificationStatus
     */
    public static function getVerificationStatusFromAccountData($linkedUser, $extensiveAccountData, $fetchServiceLinks = true) {
        $result = new VerificationStatus(VerificationStatus::ACCESS_DENIED_UNKNOWN);
        
        if($fetchServiceLinks){
            $linkedUser = LinkedUserController::getServiceLinks($linkedUser);
        }
        //Make sure data was actually provided
        if ($extensiveAccountData !== false && $extensiveAccountData !== null) {
            global $gw2i_home_world, $gw2i_linked_worlds;
            
            //Determine if access is temporary
            $temporary = $extensiveAccountData["api_key_name"] === self::TEMPORARY_API_KEY_NAME;

            $expiresIn = APIKeyManager::calculateTimeLeftBeforeKeyExpires($extensiveAccountData["last_success"]);
            
            $expired = $expiresIn <= 0;
            
            //User is banned
            if (isset($extensiveAccountData["b_banned_by"])) {
                $result = new VerificationStatus(VerificationStatus::ACCESS_DENIED_BANNED);
                $result->setBanReason($extensiveAccountData["b_reason"]);

            //Access expired
            } else if ($expired) {
                $result = new VerificationStatus(VerificationStatus::ACCESS_DENIED_EXPIRED);
                
            //Home world access
            } else if ($gw2i_home_world == $extensiveAccountData["a_world"]) {
                if ($temporary) {
                    $result = new VerificationStatus(VerificationStatus::ACCESS_GRANTED_HOME_WORLD_TEMPORARY);
                    $result->setExpires($expiresIn);
                } else {
                    $result = new VerificationStatus(VerificationStatus::ACCESS_GRANTED_HOME_WORLD);
                }

                //Linked world access
            } else if (in_array($extensiveAccountData["a_world"], $gw2i_linked_worlds)) {
                if ($temporary) {
                    $result = new VerificationStatus(VerificationStatus::ACCESS_GRANTED_LIMKED_WORLD_TEMPORARY);
                    $result->setExpires($expiresIn);
                } else {
                    $result = new VerificationStatus(VerificationStatus::ACCESS_GRANTED_LINKED_WORLD);
                }

                //Valid API Key, but not valid world
            } else {
                $result = new VerificationStatus(VerificationStatus::ACCESS_DENIED_INVALID_WORLD);
            }
        } else {
            //No results found for the users
            //not linked
            $result = new VerificationStatus(VerificationStatus::ACCESS_DENIED_ACCOUNT_NOT_LINKED);
        }
        if (
                isset($linkedUser->fetchServiceId) && isset($linkedUser->fetchServiceUserId)
                && isset($linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId])
                && $linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId]->getServiceUserId() != $linkedUser->fetchServiceUserId
        ) {
            switch ($result->getValue()) {
                case VerificationStatus::ACCESS_GRANTED_HOME_WORLD:
                case VerificationStatus::ACCESS_GRANTED_HOME_WORLD_TEMPORARY:
                case VerificationStatus::ACCESS_GRANTED_LINKED_WORLD:
                case VerificationStatus::ACCESS_GRANTED_LIMKED_WORLD_TEMPORARY:
                case VerificationStatus::ACCESS_DENIED_INVALID_WORLD:
                case VerificationStatus::ACCESS_DENIED_BANNED:
                    $result->setMirrorOwnerServiceId($linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId]->getServiceUserId());
                    break;
            }
        }
        $result->setLinkId($linkedUser->getLinkedId());
        
        //If a specific user service link is requested, attach the link attributes to the verification status
        if(isset($linkedUser->fetchServiceId) && isset($linkedUser->fetchServiceUserId)) {
            if(isset($linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId]) 
                    && $linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId]->getServiceUserId() == $linkedUser->fetchServiceUserId
                    && $linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId]->getAttributes() != null){
                
                $result->setAttributes($linkedUser->getPrimaryUserServiceLinks()[$linkedUser->fetchServiceId]->getAttributes());
                
            } else if(isset($linkedUser->getSecondaryUserServiceLinks()[$linkedUser->fetchServiceId]) 
                    && isset($linkedUser->getSecondaryUserServiceLinks()[$linkedUser->fetchServiceId][$linkedUser->fetchServiceUserId])
                    && $linkedUser->getSecondaryUserServiceLinks()[$linkedUser->fetchServiceId][$linkedUser->fetchServiceUserId]->getAttributes() != null){
                
                $result->setAttributes($linkedUser->getSecondaryUserServiceLinks()[$linkedUser->fetchServiceId][$linkedUser->fetchServiceUserId]->getAttributes());
                
            }
        }
            
        return $result;
    }
}
