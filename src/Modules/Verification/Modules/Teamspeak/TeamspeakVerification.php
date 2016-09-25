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

namespace GW2Integration\Modules\Verification\Teamspeak;

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Events\Events\UserServiceLinkEvent;
use GW2Integration\LinkedServices\Teamspeak\Teamspeak;
use GW2Integration\Modules\Verification\AbstractVerificationModule;

/**
 * Description of TeamspeakVerification
 *
 * @author Jeppe Boysen Vennekilde
 */
class TeamspeakVerification extends AbstractVerificationModule{
    const MODULE_NAME = "Teamspeak Verification Module";
    
    public function __construct() {
        parent::__construct(static::MODULE_NAME, Teamspeak::serviceId);
    }
    
    /**
     * 
     * @param UserServiceLinkEvent $event
     */
    public function onUserServiceLinkEvent(UserServiceLinkEvent $event){
        if($event->getServiceId() == $this->getServiceId()){
            $this->refreshTeamspeakAccess($event->getUserId());
        }
    }
    
    public function refreshAccess($userId) {
        $this->refreshTeamspeakAccess($userId);
    }
    
    public function handleUserWorldChanged(LinkedUser $linkedUser, $oldWorld, $newWorld) {
        $this->refreshTeamspeakAccessForLinkedUser($linkedUser);
    }

    public function refreshTeamspeakAccessForLinkedUser(LinkedUser $linkedUser){
        if(isset($linkedUser->primaryServiceIds[1])){
            $this->refreshTeamspeakAccess($linkedUser->primaryServiceIds[1][0]);
        }
        if(isset($linkedUser->secondaryServiceIds[1])){
            foreach($linkedUser->secondaryServiceIds[1] AS $tsDbid => $displayName){
                $this->refreshTeamspeakAccess($tsDbid);
            }
        }
    }
    
    /**
     * Inform the teamspeak verification bot to refresh access
     * for a given user via the HTTP interface
     * @global type $gw2i_ts_bot_remote_address
     * @param type $tsDbid
     */
    public function refreshTeamspeakAccess($tsDbid){
        Teamspeak::sendRESTCommand(array("ra" => $tsDbid));
    }
}
