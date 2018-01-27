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
use GW2Integration\Entity\LinkIdHolder;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\UserServiceLinkCreated;
use GW2Integration\Events\Events\UserServiceLinkRemoved;
use GW2Integration\Events\Events\UserServiceLinkUpdated;
use GW2Integration\Exceptions\CannotChangeServiceLink;
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
     * @param UserServiceLink|LinkedUser|int $identifier
     * @return int
     * @throws LinkedUserIdConflictException
     */
    public static function determineLinkedUserId($identifier){
        $linkId = null;
        //Check if $linkedUser is an integer
        if(!isset($identifier)){
            throw new UnableToDetermineLinkId();
        } else if(ctype_digit($identifier)){
            $linkId = $identifier;
        } else if($identifier instanceof LinkIdHolder) {
            if($identifier->getLinkedId() != null){
                $linkId = $identifier->getLinkedId();
            } else {
                global $gw2i_db_prefix;
                $pqs;
                $values = array();
                if($identifier instanceof UserServiceLink){
                    $pqs = 'SELECT link_id FROM '.$gw2i_db_prefix.'user_service_links WHERE service_user_id = ? AND service_id = ?';
                    $values = array($identifier->getServiceUserId(), $identifier->getServiceId());
                } else if($identifier instanceof LinkedUser){
                    $first = true;
                    $pqsPartial = '';
                    if(isset($identifier->fetchServiceUserId) && isset($identifier->fetchServiceId)){
                        $pqsPartial .= '(service_user_id = ? AND service_id = ?)';
                        $first = false;
                        $values[] = $identifier->fetchServiceUserId;
                        $values[] = $identifier->fetchServiceId;
                    }
                    foreach($identifier->getPrimaryUserServiceLinks() AS $userServiceLink){
                        $values[] = $userServiceLink->getServiceUserId();
                        $values[] = $userServiceLink->getServiceId();
                        if($first){
                            $first = false;
                        } else {
                            $pqsPartial .= ' OR ';
                        }
                        $pqsPartial .= '(service_user_id = ? AND service_id = ?)';
                    }

                    if(!empty($values)){
                        $pqs = 'SELECT link_id FROM '.$gw2i_db_prefix.'user_service_links WHERE '.$pqsPartial;
                    } else {
                        throw new UnableToDetermineLinkId();
                    }
                } else {
                    throw new UnableToDetermineLinkId();
                }
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

                    $identifier->setLinkedId($lastLinkId);

                    $linkId = $identifier->getLinkedId();
                } else {
                    throw new UnableToDetermineLinkId();
                }
            }
        } else {
            throw new UnableToDetermineLinkId();
        }
        return $linkId;
    }
    
    
    public static function getLinkIdFromAccountName($accountName) {
        global $gw2i_db_prefix;
        $linkId = null;
        $pqs = 'SELECT link_id FROM '.$gw2i_db_prefix.'accounts WHERE a_username LIKE ? LIMIT 1';

        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute(array("%$accountName%"));
        $linkIdRow = $ps->fetch(PDO::FETCH_NUM);

        if(!empty($linkIdRow)){
            $linkId = $linkIdRow[0];
        }
        return $linkId;
    }
    
    
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function persistUserServiceLinks($linkedUser){
        foreach($linkedUser->getPrimaryUserServiceLinks() AS $userServiceLink){
            static::persistUserServiceLink($userServiceLink);
        }
        foreach($linkedUser->getSecondaryUserServiceLinks() AS $userServiceLinks){
            foreach($userServiceLinks AS $userServiceLink){
                static::persistUserServiceLink($userServiceLink);
            }
        }
    }
    
    public static function persistUserServiceLink(UserServiceLink $userServiceLink){
        global $gw2i_db_prefix;
        //Determine the link id for the given linked user
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($userServiceLink);
        
        //Generate events that will be created by this new link
        $linkEventsFromNewLink = static::getLinkEventsFromNewLink($userServiceLink);
        
        if($linkEventsFromNewLink == false){
            //No events means that the new link is either the same as the current link, or it simply isn't worth persisting
            return false;
        }
       
        $displayName = $userServiceLink->getServiceDisplayName();
        $attributes = $userServiceLink->getAttributes();
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
            $userServiceLink->getServiceUserId(),
            $userServiceLink->getServiceId(),
            $userServiceLink->isPrimary()
        );
        if(!empty($displayName)){
            $queryParams[] = $displayName;
        }
        if(isset($attributes)){
            $queryParams[] = $attributes;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        if($userServiceLink->isPrimary()){
            static::removePrimaryLinks($linkId, $userServiceLink->getServiceId());
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
    
    /**
     * 
     * @param int[] $linkIds
     */
    public static function getServiceLinksForLinkedIds($linkIds){
        global $gw2i_db_prefix;
        
        $inQuery = implode(',', array_fill(0, count($linkIds), '?'));
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'user_service_links WHERE link_id IN('.$inQuery.')';
        
        $values = $linkIds;
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $preparedStatement->execute($values);
        $result = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    public static function getLinkEventsFromNewLink(UserServiceLink $userServiceLink){
        $affectedLinks = static::getAffectedLinksFromNewLink($userServiceLink);
        $events = array();
        $isLinkNew = true;
        //Loop through each link for the given user for the given service
        //Determine if any of the links will conflict with the new link
        //and therefore be replaced with the new link
        foreach($affectedLinks AS $affectedLink){
            if(     //Don't bother updating if nothing is different
                    //Also do not bother updating if display name is different, but new value is null
                    ($affectedLink["service_display_name"] == $userServiceLink->getServiceDisplayName() || $userServiceLink->getServiceDisplayName() == null)
                    && $affectedLink["service_user_id"] == $userServiceLink->getServiceUserId()
                    && $affectedLink["is_primary"] == $userServiceLink->getIsPrimary()
                    && $affectedLink["link_id"] == $userServiceLink->getLinkedId()
                    && $affectedLink["attributes"] == $userServiceLink->getAttributes()
                ){
                //no need to update
                return false;
            } else if(
                    //Determine if the new link is just an update of an old link
                    $affectedLink["link_id"] == $userServiceLink->getLinkedId()
                    && $affectedLink["service_user_id"] == $userServiceLink->getServiceUserId()
                ){
                $isLinkNew = false;
                $events[] =  new UserServiceLinkUpdated(
                        $userServiceLink,
                        new UserServiceLink(
                            $affectedLink["service_id"],
                            $affectedLink["service_user_id"],
                            $affectedLink["is_primary"],
                            $affectedLink["service_display_name"],
                            $affectedLink["attributes"],
                            $affectedLink["link_id"])
                        );
            } else {
                global $gw2i_linkedServices;
                $service = $gw2i_linkedServices[$affectedLink["service_id"]];
                if(!$service->allowReLinking()){
                    throw new CannotChangeServiceLink($service->getName());
                }
                
                //If the link isn't the same link, then the affected link is removed
                $events[] = new UserServiceLinkRemoved(
                        new UserServiceLink(
                            $affectedLink["service_id"],
                            $affectedLink["service_user_id"],
                            $affectedLink["is_primary"],
                            $affectedLink["service_display_name"],
                            $affectedLink["attributes"],
                            $affectedLink["link_id"])
                        );
            }
        }
        if($isLinkNew){
            $events[] = new UserServiceLinkCreated($userServiceLink);
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
    public static function getAffectedLinksFromNewLink(UserServiceLink $userServiceLink){
        global $gw2i_db_prefix;
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'user_service_links WHERE (service_id = ? AND service_user_id = ?)';
        $values = array(
            $userServiceLink->getServiceId(),
            $userServiceLink->getServiceUserId(),
        );
        
        //There can be multiple secondary links on the same service, so only run this check if the link is primary
        if($userServiceLink->isPrimary()){
            $preparedQueryString .= " OR (link_id = ? AND service_id = ? AND is_primary = 1)";
            $values[] = $userServiceLink->getLinkedId();
            $values[] = $userServiceLink->getServiceId();
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
            if(!empty($linkData["attributes"])){
                $json = json_decode($linkData["attributes"], true);
                if(isset($json[$attributeName])){
                    unset($json[$attributeName]);
                    static::setAttributes($linkData["service_user_id"], $linkData["service_id"], json_encode($json));
                }
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
                $userServiceLink = new UserServiceLink(
                            $serviceLink["service_id"], 
                            $serviceLink["service_user_id"], 
                            $serviceLink["is_primary"],
                            $serviceLink["service_display_name"], 
                            $serviceLink["attributes"]);
                $userServiceLink->setLinkedId($serviceLink["link_id"]);
                $event = new UserServiceLinkRemoved($userServiceLink);
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
