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

namespace GW2Integration\Controller;

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Entity\LinkIdHolder;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * 
 */
class LinkedUserController {

    const TEMPORARY_API_KEY_NAME = "temporary";
    const TEMPORARY_API_KEY_PERMISSIONS = "none";
    /**
     * 
     * @param LinkedUser|UserServiceLink|int $userIdentifier
     */
    public static function getServiceLinks($userIdentifier){
        $serviceLinks = LinkingPersistencyHelper::getServiceLinks($userIdentifier);
        if($userIdentifier instanceof LinkedUser){
            $linkedUser = $userIdentifier;
            //Clear current service ids
            $linkedUser->clearPrimaryUserServiceLinks();
            $linkedUser->clearSecondaryUserServiceLinks();
        } else {
            $linkedUser = new LinkedUser();
        }
        foreach($serviceLinks AS $serviceLink){
            $linkedUser->setLinkedId($serviceLink["link_id"]);
            $linkedUser->addUserServiceLink(new UserServiceLink($serviceLink["service_id"], $serviceLink["service_user_id"], $serviceLink["is_primary"] == 1, $serviceLink["service_display_name"], $serviceLink["attributes"]));
        }
        if($userIdentifier != $linkedUser && $userIdentifier instanceof LinkIdHolder){
            $userIdentifier->setLinkedId($linkedUser->getLinkedId());
        }
        return $linkedUser;
    }

    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $serviceUserId
     * @param type $serviceId
     */
    public static function deleteServiceLink($linkedUser, $serviceUserId, $serviceId){
        return LinkingPersistencyHelper::deleteServiceLink($linkedUser, $serviceUserId, $serviceId);
    }
    
    /**
     * 
     * @global type $session_expiration_periode
     * @global type $gw2i_linkedServices
     * @global type $session_expiration_periode
     * @param UserServiceLink $mainUserServiceLink
     * @param UserServiceLink[] $userServiceLinks
     */
    public static function mergeUserServiceLinks($mainUserServiceLink, $userServiceLinks){
        global $logger;
        //Fetch service links
        $linkedUser = static::getServiceLinks($mainUserServiceLink);
        $linkedUsers = array();
        foreach($userServiceLinks AS $userServiceLink){
            try {
                //This should already be null, but in case it isn't, set it to null
                //The reason is that it isn't possible to link an entire user with another user
                //Only a user service link with another user
                $userServiceLink->setLinkedId(null);
                
                $linkedUsers[] = static::getServiceLinks($userServiceLink);
            } catch (UnableToDetermineLinkId $ex) {
                $linkedUsers[] = null;
            }
        }
        
        //Proccess
        foreach($userServiceLinks AS $key => $userServiceLink){
            //Link doesn't exist, so it can be safely added to the primary link
            if($userServiceLink->getLinkedId() == null){
                //Can safely add as link
                $linkedUser->addUserServiceLink(
                        new UserServiceLink(
                            $userServiceLink->getServiceId(),
                            $userServiceLink->getServiceUserId(), 
                            $userServiceLink->isPrimary(),
                            $userServiceLink->getServiceDisplayName(),
                            $userServiceLink->getAttributes()));
                
                
            //Link is already present on another user
            } else if($mainUserServiceLink->getLinkedId() != $userServiceLink->getLinkedId()){
                $conflictLinkedUser = $linkedUsers[$key];
                $logger->info("Attampting conflict merge of linked users ".$linkedUser->compactString() ." and ".$conflictLinkedUser->compactString());
                /* @var $linkedUser LinkedUser */
                //$extensiveData1 = GW2DataPersistence::getExtensiveAccountData($primaryLinkedUser);
                //$extensiveData2 = GW2DataPersistence::getExtensiveAccountData($userServiceLink);
                
                $linkedUser->addUserServiceLink(
                        new UserServiceLink(
                            $userServiceLink->getServiceId(),
                            $userServiceLink->getServiceUserId(), 
                            $userServiceLink->isPrimary(),
                            $userServiceLink->getServiceDisplayName(),
                            $userServiceLink->getAttributes()));
                
                
                if(count($conflictLinkedUser->getPrimaryUserServiceLinks()) <= 1){
                    //Delete conflict link data, as the only primary reference to the link is removed
                    $logger->info($conflictLinkedUser->compactString()." Deleted, as last user service link got merged with ".$linkedUser->compactString());
                    GW2DataPersistence::deleteAllData($conflictLinkedUser);
                }
            }
        }
        LinkingPersistencyHelper::persistUserServiceLinks($linkedUser);
        return $linkedUser;
    }
}
