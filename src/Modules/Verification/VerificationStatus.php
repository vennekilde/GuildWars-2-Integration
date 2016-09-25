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

use GW2Integration\Utils\BetterEnum;

/**
 * Description of VerificationStatus
 *
 * @author Jeppe Boysen Vennekilde
 */
class VerificationStatus extends BetterEnum {

    private $expires;
    private $banReason;
    private $linkId;
    private $mirrorOwnerServiceId;
    private $attributes;

    const __default = self::ACCESS_DENIED_ACCOUNT_NOT_LINKED;
    const ACCESS_GRANTED_HOME_WORLD = 0;
    const ACCESS_GRANTED_LINKED_WORLD = 1;
    const ACCESS_GRANTED_HOME_WORLD_TEMPORARY = 2;
    const ACCESS_GRANTED_LIMKED_WORLD_TEMPORARY = 3;
    const ACCESS_DENIED_ACCOUNT_NOT_LINKED = 4;
    const ACCESS_DENIED_EXPIRED = 5;
    const ACCESS_DENIED_INVALID_WORLD = 6;
    const ACCESS_DENIED_BANNED = 7;
    const ACCESS_DENIED_UNKNOWN = 8;

    function getExpires() {
        return $this->expires;
    }

    function setExpires($expires) {
        $this->expires = $expires;
    }

    function getBanReason() {
        return $this->banReason;
    }

    function setBanReason($banReason) {
        $this->banReason = $banReason;
    }

    function getLinkId() {
        return $this->linkId;
    }

    function setLinkId($linkId) {
        $this->linkId = $linkId;
    }
    
    function getMirrorOwnerServiceId() {
        return $this->mirrorOwnerServiceId;
    }

    function setMirrorOwnerServiceId($mirrorOwnerServiceId) {
        $this->mirrorOwnerServiceId = $mirrorOwnerServiceId;
    }
    
    public function getAttributes() {
        return $this->attributes;
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }
        
    function toJSON($useEnumId){
        $accessStatus = null;
    
        if($useEnumId){
            $accessStatus = $this->getValue();
        } else {
            $accessStatus = array_search($this->getValue(),$this->getConstants(false));
        }

        //Add access status to output
        $result = array("status" => $accessStatus);

        //Add expires data if not access expires
        if($this->getExpires() != null){
            $result["expires-in"] = $this->getExpires();
        }
        //add ban data if banned
        if($this->getBanReason() != null){
            $result["ban-reason"] = $this->getBanReason();
        }
        //add ban data if banned
        if($this->getAttributes() != null){
            $result["attributes"] = $this->getAttributes();
        }
        //Add mirror owner data if given user is not the primary user
        if($this->getMirrorOwnerServiceId() != null){
            $result["mirror-link-owner"] = array(
                "link_id" => $this->getLinkId(),
                "mirror_owner_user_id" => $this->getMirrorOwnerServiceId(),
            );
        }
        
        return $result;
    }
}