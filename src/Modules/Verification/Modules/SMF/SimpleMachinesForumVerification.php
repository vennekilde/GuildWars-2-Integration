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
 
namespace GW2Integration\Modules\Verification\SMF;

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\Events\Events\UserServiceLinkEvent;
use GW2Integration\LinkedServices\SMF\SimpleMachinesForum;
use GW2Integration\Modules\Verification\AbstractVerificationModule;
use GW2Integration\Persistence\Helper\VerificationEventPersistence;
use GW2Integration\Persistence\Persistence;
use PDO;
 
/**
 * Description of SimpleMachinesForumVerification
 *
 * @author Jeppe Boysen Vennekilde
 */
class SimpleMachinesForumVerification extends AbstractVerificationModule{
    const MODULE_NAME = "Simple Machines Forum Verification Module";
    
    public function __construct() {
        parent::__construct(static::MODULE_NAME, SimpleMachinesForum::serviceId);
    }
    
    public function onAPISyncCompleted(APISyncCompleted $event){
        $this->refreshAccess();
    }
    
    /**
     * 
     * @param UserServiceLinkEvent $event
     */
    public function onUserServiceLinkEvent(UserServiceLinkEvent $event){
        if($event->getUserServiceLink()->getServiceId() == $this->getServiceId()){
            $this->refreshAccess($event->getUserServiceLink()->getServiceUserId());
        }
    }
    
    public function handleUserWorldChanged(LinkedUser $linkedUser, $oldWorld, $newWorld) {
        if(isset($linkedUser->getPrimaryUserServiceLinks()[$this->getServiceId()])){
            $this->refreshAccess($linkedUser->getPrimaryUserServiceLinks()[$this->getServiceId()]->getServiceUserId());
        }
        if(isset($linkedUser->getSecondaryUserServiceLinks()[$this->getServiceId()])){
            foreach($linkedUser->getSecondaryUserServiceLinks()[$this->getServiceId()] AS $userServiceLink){
                /* @var $userServiceLink UserServiceLink */
                $this->refreshAccess($userServiceLink->getServiceId());
            }
        }
    }
    
    public function refreshAccess($userId = null){
        $this->removeIncorrectGroupings($userId);
        $this->addMissingGroupings($userId);
    }
    
    public function removeIncorrectGroupings($userId = null){
        global $gw2i_db_prefix;
        $pq = Persistence::getDBEngine()->prepare('CALL '.$gw2i_db_prefix.'smf_find_incorrect_groupings(?)');
        
        $pq->execute(array($userId));
        
        $result = $pq->fetchAll(PDO::FETCH_NUM);
        
        if(is_array($result)){
            $incorrectGroupingsLinkIds = array();
            $incorrectGroupingsMemberIds = array();
            
            foreach($result AS $incorrectGrouping){
                $incorrectGroupingsLinkIds[$incorrectGrouping[2]][] = $incorrectGrouping[0];
                $incorrectGroupingsMemberIds[$incorrectGrouping[2]][] = $incorrectGrouping[1];
            }

            foreach($incorrectGroupingsMemberIds AS $groupId => $memberIds){
                $linkIds = array_values($incorrectGroupingsLinkIds[$groupId]);
                $this->removeUsersFromForumUserGroup($linkIds, $memberIds, array($groupId));
            }
        }
    }
    
    public function addMissingGroupings($userId = null){
        global $gw2i_db_prefix;
        $pq = Persistence::getDBEngine()->prepare('CALL '.$gw2i_db_prefix.'smf_find_missing_groupings(?)');
        
        $pq->execute(array($userId));
        
        $result = $pq->fetchAll(PDO::FETCH_NUM);
        
        if(is_array($result)){
            $missingGroupingsLinkIds = array();
            $missingGroupingsMemberIds = array();
            foreach($result AS $incorrectGrouping){
                $missingGroupingsLinkIds[$incorrectGrouping[2]][] = $incorrectGrouping[0];
                $missingGroupingsMemberIds[$incorrectGrouping[2]][] = $incorrectGrouping[1];
            }

            foreach($missingGroupingsMemberIds AS $groupId => $memberIds){
                $linkIds = array_values($missingGroupingsLinkIds[$groupId]);
                $this->addUsersToForumUserGroup($linkIds, $memberIds, $groupId);
            }
        }
    }
     
    /**
     * 
     * @global \GW2Integration\LinkedServices\SMF\type $smcFunc
     * @param array $members
     * @param array $groupId
     */
    public static function addUsersToForumUserGroup($linkIds, $members, $groupId){
        global $smcFunc, $logger;
        if(!is_array($linkIds)){
            $linkIds = array($linkIds);
        }
        if(!is_array($members)){
            $members = array($members);
        }
        
        //Taken from Subs-Membergroups.php line 553
        $request = $smcFunc['db_query']('', '
            UPDATE {db_prefix}members
            SET
                id_group = CASE WHEN id_group = {int:regular_group} THEN {int:id_group} ELSE id_group END,
                additional_groups = CASE WHEN id_group = {int:id_group} THEN additional_groups
                    WHEN additional_groups = {string:blank_string} THEN {string:id_group_string}
                    ELSE CONCAT(additional_groups, {string:id_group_string_extend}) END
            WHERE id_member IN ({array_int:member_list})
                AND id_group != {int:id_group}
                AND FIND_IN_SET({int:id_group}, additional_groups) = 0',
            array(
                'member_list' => $members,
                'regular_group' => 0,
                'id_group' => $groupId,
                'blank_string' => '',
                'id_group_string' => (string) $groupId,
                'id_group_string_extend' => ',' . $groupId,
            )
        );
         
        //Logging
        for($i = 0; $i < count($members); $i++){
            $userId = $members[$i];
            $linkId = $linkIds[$i];
            VerificationEventPersistence::persistVerificationEvent($linkId, VerificationEventPersistence::SERVICE_GROUP_EVENT, SimpleMachinesForum::serviceId.",".$groupId.",1");
            $logger->info("Added user \"$userId\" with link-id \"$linkId\" to SMF User Group $groupId");
        }
    }
    /**
     * 
     * @global type $smcFunc
     * @param array $members
     * @param array $groups
     */
    public static function removeUsersFromForumUserGroup($linkIds, $members, $groups){
        //global
        global $smcFunc, $logger;
         
        if(!is_array($linkIds)){
            $linkIds = array($linkIds);
        }
        if(!is_array($members)){
            $members = array($members);
        }
        if(!is_array($groups)){
            $groups = array($groups);
        }
         
        // Taken from Subs-Membergroups.php line 372
        // First, reset those who have this as their primary group - this is the easy one.
        $smcFunc['db_query']('', '
            UPDATE {db_prefix}members
            SET id_group = {int:regular_member}
            WHERE id_group IN ({array_int:group_list})
                AND id_member IN ({array_int:member_list})',
            array(
                'group_list' => $groups,
                'member_list' => $members,
                'regular_member' => 0,
            )
        );

        // Those who have it as part of their additional group must be updated the long way... sadly.
        $request = $smcFunc['db_query']('', '
            SELECT id_member, additional_groups
            FROM {db_prefix}members
            WHERE (FIND_IN_SET({raw:additional_groups_implode}, additional_groups) != 0)
                AND id_member IN ({array_int:member_list})
            LIMIT ' . count($members),
            array(
                'member_list' => $members,
                'additional_groups_implode' => implode(', additional_groups) != 0 OR FIND_IN_SET(', $groups),
            )
        );
        $updates = array();
        while ($row = $smcFunc['db_fetch_assoc']($request))
        {
            $updates[$row['additional_groups']][] = $row['id_member'];
        }
        $smcFunc['db_free_result']($request);

        foreach ($updates as $additional_groups => $memberArray){
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}members
                SET additional_groups = {string:additional_groups}
                WHERE id_member IN ({array_int:member_list})',
                array(
                    'member_list' => $memberArray,
                    'additional_groups' => implode(',', array_diff(explode(',', $additional_groups), $groups)),
                )
            );
        }
         
        //Logging
        for($i = 0; $i < count($members); $i++){
            foreach($groups AS $groupId){
                $userId = $members[$i];
                $linkId = $linkIds[$i];
                VerificationEventPersistence::persistVerificationEvent($linkId, VerificationEventPersistence::SERVICE_GROUP_EVENT, SimpleMachinesForum::serviceId.",".$groupId.",0");
                $logger->info("Removed user \"$userId\" with link-id \"$linkId\" from SMF User Group $groupId");
            }
        }
    }
}