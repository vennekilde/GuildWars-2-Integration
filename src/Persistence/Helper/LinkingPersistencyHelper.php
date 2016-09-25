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
use GW2Integration\Events\Events\UserServiceLinkCreated;
use GW2Integration\Events\Events\UserServiceLinkRemoved;
use GW2Integration\Events\Events\UserServiceLinkUpdated;
use GW2Integration\Exceptions\LinkedUserIdConflictException;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Persistence;
use PDO;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * Description of GW2LinkingPersistencyHelper
 *
 * @author Jeppe Boysen Vennekilde
 */
class LinkingPersistencyHelper {
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param LinkedUser $linkedUser Can also be int for the link-id
     * @return int
     * @throws LinkedUserIdConflictException
     */
    public static function determineLinkedUserId($linkedUser){
        $linkId = null;
        //Check if $linkedUser is an integer
        if(!isset($linkedUser)){
            throw new UnableToDetermineLinkId();
        } else if(ctype_digit($linkedUser)){
            $linkId = $linkedUser;
        } else if($linkedUser->getLinkedId() != null){
            $linkId = $linkedUser->getLinkedId();
        } else {
            $values = array();
            $first = true;
            $pqsPartial = '';
            if(isset($linkedUser->fetchServiceUserId) && isset($linkedUser->fetchServiceId)){
                $pqsPartial .= '(service_user_id = ? AND service_id = ?)';
                $first = false;
                $values[] = $linkedUser->fetchServiceUserId;
                $values[] = $linkedUser->fetchServiceId;
            }
            if(!empty($linkedUser->primaryServiceIds)){
                foreach($linkedUser->primaryServiceIds AS $serviceId => $userId){
                    $values[] = $userId[0];
                    $values[] = $serviceId;
                    if($first){
                        $first = false;
                    } else {
                        $pqsPartial .= ' OR ';
                    }
                    $pqsPartial .= '(service_user_id = ? AND service_id = ?)';
                }
            }
            
            global $gw2i_db_prefix;
            if(!empty($values)){
                $pqs = 'SELECT link_id FROM '.$gw2i_db_prefix.'user_service_links WHERE '.$pqsPartial;

                $ps = Persistence::getDBEngine()->prepare($pqs);
                $ps->execute($values);
                $linkIds = $ps->fetch(PDO::FETCH_NUM);

                if($linkIds != false && isset($linkIds)){
                    //Ensure link is the same for all
                    $lastLinkId = null;
                    foreach($linkIds AS $linkId){
                        if(isset($lastLinkId)){
                            if($lastLinkId != $linkId){
                                throw new LinkedUserIdConflictException();
                            }
                        }
                        $lastLinkId = $linkId;
                    }

                    $linkedUser->setLinkedId($lastLinkId);
                    
                    $linkId = $linkedUser->getLinkedId();
                } else {
                    throw new UnableToDetermineLinkId();
                }
            } else {
                throw new UnableToDetermineLinkId();
            }
        }
        return $linkId;
    }
    
    
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function persistServiceUserLinks($linkedUser){
        foreach($linkedUser->primaryServiceIds AS $serviceId => $userId){
            static::persistServiceUserLink($linkedUser, $userId[0], $serviceId, $userId[1], true);
        }
        foreach($linkedUser->secondaryServiceIds AS $serviceId => $userId){
            static::persistServiceUserLink($linkedUser, $userId[0], $serviceId, $userId[1], false);
        }
    }
    
    public static function persistServiceUserLink($linkedUser, $serviceUserId, $serviceId, $displayName = null, $isPrimary = true, $attributes = null){
        global $gw2i_db_prefix;
        //Determine the link id for the given linked user
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        //Generate events that will be created by this new link
        $linkEventsFromNewLink = static::getLinkEventsFromNewLink($linkId, $serviceUserId, $serviceId, $displayName, $isPrimary, $attributes);
        
        if($linkEventsFromNewLink == false){
            //No events means that the new link is either the same as the current link, or it simply isn't worth persisting
            return false;
        }
       
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'user_service_links (link_id, service_user_id, service_id, is_primary' . (!empty($displayName) ? ", service_display_name" : "") . (isset($attributes) ? ", attributes" : "") . ')
                VALUES(?,?,?,?' . (!empty($displayName) ? ",?" : "") . (isset($attributes) ? ",?" : "") . ')
            ON DUPLICATE KEY UPDATE 
                link_id = VALUES(link_id),
                service_user_id = VALUES(service_user_id),
                is_primary = VALUES(is_primary)'
                . (!empty($displayName) ? ", service_display_name = VALUES(service_display_name)" : "")
                . (isset($attributes) ? ", attributes = VALUES(attributes)" : "");
        
        $queryParams = array(
            $linkId,
            $serviceUserId,
            $serviceId,
            $isPrimary
        );
        if(!empty($displayName)){
            $queryParams[] = $displayName;
        }
        if(isset($attributes)){
            $queryParams[] = $attributes;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        if($isPrimary){
            static::removePrimaryLinks($linkId, $serviceId);
        }
        
        $result = $preparedStatement->execute($queryParams);
        
        foreach($linkEventsFromNewLink as $event){
            EventManager::fireEvent($event);
        }
        
        return $result;
    }
    
    public static function removePrimaryLinks($linkId, $serviceId){
        global $gw2i_db_prefix;
        $preparedQueryString = 'DELETE FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id = ? AND service_id = ? AND is_primary = 1;';
        $values = array(
            $linkId,
            $serviceId
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($values);
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function getServiceLinks($linkedUser){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id = ?';
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute(array($linkId));
        $result = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    public static function getLinkEventsFromNewLink($linkId, $serviceUserId, $serviceId, $displayName, $isPrimary, $attributes){
        $affectedLinks = static::getAffectedLinksFromNewLink($linkId, $isPrimary, $serviceUserId, $serviceId);
        $events = array();
        $isLinkNew = true;
        //Loop through each link for the given user for the given service
        //Determine if any of the links will conflict with the new link
        //and therefore be replaced with the new link
        foreach($affectedLinks AS $affectedLink){
            if(     //Don't bother updating if nothing is different
                    //Also do not bother updating if display name is different, but new value is null
                    ($affectedLink["service_display_name"] == $displayName || $displayName == null)
                    && $affectedLink["service_user_id"] == $serviceUserId
                    && $affectedLink["is_primary"] == $isPrimary
                    && $affectedLink["link_id"] == $linkId
                    && $affectedLink["attributes"] == $attributes
                ){
                //no need to update
                return false;
            } else if(
                    //Determine if the new link is just an update of an old link
                    $affectedLink["link_id"] == $linkId
                    && $affectedLink["service_user_id"] == $serviceUserId
                ){
                $isLinkNew = false;
                $events[] =  new UserServiceLinkUpdated(
                        $linkId, 
                        $serviceId, 
                        $serviceUserId, 
                        $displayName, 
                        $isPrimary,
                        $attributes,
                        $affectedLink["service_display_name"], 
                        $affectedLink["is_primary"],
                        $affectedLink["attributes"]);
            } else {
                //If the link isn't the same link, then the affected link is removed
                $events[] =  new UserServiceLinkRemoved(
                        $affectedLink["link_id"], 
                        $affectedLink["service_id"], 
                        $affectedLink["service_user_id"], 
                        $affectedLink["service_display_name"], 
                        $affectedLink["is_primary"],
                        $affectedLink["attributes"]);
            }
        }
        if($isLinkNew){
            $events[] =  new UserServiceLinkCreated(
                    $linkId, 
                    $serviceId, 
                    $serviceUserId, 
                    $displayName, 
                    $isPrimary,
                    $attributes);
        }
        return $events;
    }
    
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\type $gw2i_db_prefix
     * @param type $linkId
     * @param type $isPrimary
     * @param type $serviceUserId
     * @param type $serviceId
     * @return type
     */
    public static function getAffectedLinksFromNewLink($linkId, $isPrimary, $serviceUserId, $serviceId){
        global $gw2i_db_prefix;
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'user_service_links WHERE (service_id = ? AND service_user_id = ?)';
        $values = array(
            $serviceId,
            $serviceUserId,
        );
        
        //There can be multiple secondary links on the same service, so only run this check if the link is primary
        if($isPrimary){
            $preparedQueryString .= " OR (link_id = ? AND service_id = ? AND is_primary = 1)";
            $values[] = $linkId;
            $values[] = $serviceId;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($values);
        $result = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    /**
     * Get data for a specific user service link
     * @param LinkedUser $linkedUser
     */
    public static function getServiceLink($linkedUser, $serviceId, $serviceUserId){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id = ? AND service_id = ? AND service_user_id = ?';
        
        $values = array(
            $linkId,
            $serviceId,
            $serviceUserId
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($values);
        
        $result = $preparedStatement->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function getServiceLinksForService($linkedUser, $serviceId){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id = ? AND service_id = ?';
        $values = array(
            $linkId,
            $serviceId
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($values);
        $result = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    public static function setDisplayName($serviceUserId, $serviceId, $displayName = null){
        global $gw2i_db_prefix;
        $preparedQueryString = 'UPDATE '.$gw2i_db_prefix.'user_service_links SET service_display_name = ? WHERE service_user_id = ? AND service_id = ?';
        $queryParams = array(
            $displayName,
            $serviceUserId,
            $serviceId
        );
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($queryParams);
    }
    
    public static function setAttributes($serviceUserId, $serviceId, $attributes = null){
        global $gw2i_db_prefix;
        $preparedQueryString = 'UPDATE '.$gw2i_db_prefix.'user_service_links SET attributes = ? WHERE service_user_id = ? AND service_id = ?';
        $queryParams = array(
            $attributes,
            $serviceUserId,
            $serviceId
        );
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($queryParams);
    }
    
    public static function setAttribute($serviceUserId, $serviceId, $attributeName, $attributeValue){
        global $gw2i_db_prefix;
        
        $preparedQueryString = 'SELECT service_id, service_user_id, attributes FROM '.$gw2i_db_prefix.'user_service_links WHERE service_user_id = ? AND service_id = ?';
        $queryParams = array(
            $serviceUserId,
            $serviceId
        );
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($queryParams);
        
        $response = $preparedStatement->fetch(PDO::FETCH_ASSOC);
        if(empty($response["attributes"])){
            $json = array();
        } else {
            $json = json_decode($response["attributes"], true);
        }
        
        if(empty($attributeValue)){
            unset($json[$attributeName]);
        } else {
            $json[$attributeName] = $attributeValue;
        }
        static::setAttributes($serviceUserId, $serviceId, json_encode($json));
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $attributeName
     */
    public static function removeAttributeFromAllUserLinks($linkedUser, $attributeName){
        global $gw2i_db_prefix;
        $userId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = 'SELECT service_id, service_user_id, attributes FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id = ?';
        $queryParams = array(
            $userId
        );
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($queryParams);
        
        $response = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($response AS $linkData){
            $json = json_decode($linkData["attributes"], true);
            if(isset($json[$attributeName])){
                unset($json[$attributeName]);
                static::setAttributes($linkData["service_user_id"], $linkData["service_id"], json_encode($json));
            }
        }
    }
    
    /**
     * @param LinkedUser $linkedUser
     * @return string can be either an sql query to find the id, or just the id, if the $linkedUser contains the link_id
     */
    /*public static function getLinkIdQuery($linkedUser, $wrap = false) {
        $userIdQuery = null;
        if (isset($linkedUser->linkedId)) {
            $userIdQuery = $linkedUser->linkedId;
        } else if (isset($linkedUser->fetchServiceUserId) && isset($linkedUser->fetchServiceId)) {
            $userIdQuery = "(SELECT link_id FROM gw2_linked_services WHERE service_user_id = " . static::bigintval($linkedUser->fetchServiceUserId) . " AND service_id = " . static::bigintval($linkedUser->fetchServiceId) . ")";
            if($wrap){
                $userIdQuery = "(SELECT * FROM ".$userIdQuery." AS gw2_ls)";
            }
        }
        return $userIdQuery;
    }*/

    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $serviceUserId
     * @param type $serviceId
     */
    public static function deleteServiceLink($linkedUser, $serviceUserId, $serviceId){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        $preparedQueryString = '
            DELETE FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id = ? AND service_user_id = ? AND service_id = ?';
        $queryParams = array(
            $linkId,
            $serviceUserId,
            $serviceId
        );
        
        // Get link data before deletion, so an event can be thrown
        $serviceLink = static::getServiceLink($linkedUser, $serviceId, $serviceUserId);
        
        //Check if the link actually exists in the first place
        if($serviceLink != false){
            $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

            $preparedStatement->execute($queryParams);

            if($preparedStatement->rowCount() > 0){
                $event = new UserServiceLinkRemoved(
                            $serviceLink["link_id"], 
                            $serviceLink["service_id"], 
                            $serviceLink["service_user_id"], 
                            $serviceLink["service_display_name"], 
                            $serviceLink["is_primary"]);
                EventManager::fireEvent($event);
            }
        }
        
    }
    
    /**
     * @param LinkedUser $linkedUser
     * @return array(string, integer) [0] = user identity column, [1] = user id. Returns null if no link data given
     */
    /*public static function getUserIdColumnAndValue($linkedUser) {
        $userColumn = null;
        $userId = null;
        if (isset($linkedUser->linkedId)) {
            $userId = $linkedUser->linkedId;
            $userColumn = "WHERE gw2_accounts.link_id = ?";
        } else if (isset($linkedUser->fetchServiceUserId) && isset($linkedUser->fetchServiceId)) {
            $userId = array($linkedUser->fetchServiceUserId, $linkedUser->fetchServiceId);
            $userColumn = "INNER JOIN gw2_linked_services ls ON ls.link_id = gw2_accounts.link_id WHERE ls.service_user_id = ? AND ls.service_id = ?";
        }
        if ($userColumn == null) {
            return null;
        }
        return array($userColumn, $userId);
    }*/
}
