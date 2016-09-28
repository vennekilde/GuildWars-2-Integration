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

namespace GW2Integration\Entity;

use function GuzzleHttp\json_encode;

/**
 * Description of UserId
 *
 * @author Jeppe Boysen Vennekilde
 */
class LinkedUser extends LinkIdHolder{
    
    /**
     * 0 = Website Service
     * 1 = Teamspeak service
     * 2 = Discord
     * @var UserServiceLink[]
     */
    public $primaryUserServiceLinks = array();
    
    /**
     * 0 = Website Service
     * 1 = Teamspeak service
     * 2 = Discord
     * @var UserServiceLink[][]
     */
    public $secondaryUserServiceLinks = array();
    
    public $fetchServiceUserId;
    public $fetchServiceId;
    public $fetchServiceDisplayName;
    
    public function __construct($linkId = null) {
        parent::__construct($linkId);
    }
    
    public function setLinkedId($linkedId) {
        parent::setLinkedId($linkedId);
        foreach($this->primaryUserServiceLinks AS $userServiceLink){
            $userServiceLink->setLinkedId($linkedId);
        }
        foreach($this->secondaryUserServiceLinks AS $userServiceLinks){
            foreach($userServiceLinks AS $userServiceLink){
                $userServiceLink->setLinkedId($linkedId);
            }
        }
    }
    
    public function addUserServiceLink(UserServiceLink $userServiceLink){
        if(isset($userServiceLink)){
            $userServiceLink->setLinkedId($this->getLinkedId());
            if($userServiceLink->isPrimary()){
                $this->primaryUserServiceLinks[$userServiceLink->getServiceId()] = $userServiceLink;
            } else {
                if(!isset($this->secondaryUserServiceLinks[$userServiceLink->getServiceId()])){
                    $this->secondaryUserServiceLinks[$userServiceLink->getServiceId()] = array();
                }
                $this->secondaryUserServiceLinks[$userServiceLink->getServiceId()][] = $userServiceLink;
            }
        }
    }
    
    /**
     * 
     * @return UserServiceLink[]
     */
    public function getPrimaryUserServiceLinks() {
        return $this->primaryUserServiceLinks;
    }

    /**
     * 
     * @return UserServiceLink[][]
     */
    public function getSecondaryUserServiceLinks() {
        return $this->secondaryUserServiceLinks;
    }
    
    
    
    public function clearPrimaryUserServiceLinks(){
        $this->primaryUserServiceLinks = array();
    }
    
    public function clearSecondaryUserServiceLinks(){
        $this->secondaryUserServiceLinks = array();
    }
    
    public function addOnLinkedIdSetListener($listenerMethod){
        if($this->getLinkedId() != null){
            $this->linkedIdSetListeners[] = $listenerMethod;
        } else {
            $listenerMethod();
        }
    }
    
    public function compactString(){
        $compactString = "<LinkedUser ID[".$this->getLinkedId()."]";
        foreach($this->primaryUserServiceLinks AS $userServiceLink){
            $compactString .= " SID-".$userServiceLink->getServiceId()."[".$userServiceLink->getServiceUserId()."]]";
        }
        return $compactString . ">";
    }
    
    public function toString() {
        $toString = "id[".$this->getLinkedId()."] Linked Primary Services: ".json_encode($this->primaryUserServiceLinks);
        if(isset($this->fetchServiceId) && isset($this->fetchServiceUserId)){
            $toString .= " Fetch ID[$this->fetchServiceId - $this->fetchServiceUserId : $this->fetchServiceDisplayName]";
        }
        return $toString;
    }
    public function __toString() {
        return $this->toString();
    }
   
    public function jsonSerialize() {
        $parentJson = parent::jsonSerialize();
        return array_merge($parentJson, get_object_vars($this));
    }
}
