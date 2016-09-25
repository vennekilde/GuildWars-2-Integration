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

/**
 * Description of UserId
 *
 * @author Jeppe Boysen Vennekilde
 */
class LinkedUser {
    /**
     * The database id of the linked user
     * @var integer
     */
    private $linkedId;
    
    /**
     * 0 = Website Service
     * 1 = Teamspeak service
     * 2 = Discord
     * @var Array(Array(Long, String)) 
     */
    public $primaryServiceIds = array();
    
    /**
     * 0 = Website Service
     * 1 = Teamspeak service
     * 2 = Discord
     * @var Array(Array(Long => String)) 
     */
    public $secondaryServiceIds = array();
    
    public $fetchServiceUserId;
    public $fetchServiceId;
    public $fetchServiceDisplayName;
    
    private $linkedIdSetListeners = array();

    public function setLinkedId($linkedId){
        $this->linkedId = $linkedId;
        
        foreach($this->linkedIdSetListeners AS $listener){
            $listener();
        }
        $this->linkedIdSetListeners = array();
    }
    
    public function getLinkedId(){
        return $this->linkedId;
    }
    
    
    public function setPrimaryarySeviceId($serviceUserId, $serviceId, $displayName = null, $attributes = null){
        $this->primaryServiceIds[$serviceId] = array($serviceUserId, $displayName, $attributes);
    }
    
    public function addSecondarySeviceId($serviceUserId, $serviceId, $displayName = null, $attributes = null){
        if(!isset($this->secondaryServiceIds[$serviceId])){
            $this->secondaryServiceIds[$serviceId] = array();
        }
        $this->secondaryServiceIds[$serviceId][$serviceUserId] = array($displayName, $attributes);
    }
    
    public function addOnLinkedIdSetListener($listenerMethod){
        if(!isset($this->linkedId)){
            $this->linkedIdSetListeners[] = $listenerMethod;
        } else {
            $listenerMethod();
        }
    }
    
    public function compactString(){
        $compactString = "<LinkedUser ID[$this->linkedId]";
        foreach($this->primaryServiceIds AS $serviceId => $primaryServiceId){
            $compactString .= " SID-".$serviceId."[$primaryServiceId[0]]";
        }
        return $compactString . ">";
    }
    
    public function toString() {
        $toString = "id[$this->linkedId] Linked Primary Services: ".json_encode($this->primaryServiceIds);
        if(isset($this->fetchServiceId) && isset($this->fetchServiceUserId)){
            $toString .= " Fetch ID[$this->fetchServiceId - $this->fetchServiceUserId : $this->fetchServiceDisplayName]";
        }
        return $toString;
    }
    public function __toString() {
        return $this->toString();
    }
   
}
