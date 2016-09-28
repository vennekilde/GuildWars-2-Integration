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
class UserServiceLink extends LinkIdHolder{

    private $linkId;
    private $serviceId;
    
    private $serviceUserId;
    
    private $serviceDisplayName;
    
    private $isPrimary;
    
    private $attributes;
    
    public function __construct($serviceId, $serviceUserId, $isPrimary, $serviceDisplayName = null, $attributes = null, $linkId = null) {
        parent::__construct($linkId);
        $this->serviceId = $serviceId;
        $this->serviceUserId = $serviceUserId;
        $this->isPrimary = $isPrimary;
        $this->serviceDisplayName = $serviceDisplayName;
        $this->attributes = $attributes;
    }

    
    public function getServiceId() {
        return $this->serviceId;
    }

    public function getIsPrimary() {
        return $this->isPrimary;
    }
    
    public function getServiceUserId() {
        return $this->serviceUserId;
    }

    public function getServiceDisplayName() {
        return $this->serviceDisplayName;
    }

    public function isPrimary() {
        return $this->isPrimary;
    }

    public function getAttributes() {
        return $this->attributes;
    }
    
    public function setServiceDisplayName($serviceDisplayName) {
        $this->serviceDisplayName = $serviceDisplayName;
        return $this;
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }
    
    public function jsonSerialize() {
        $parentJson = parent::jsonSerialize();
        return array_merge($parentJson, get_object_vars($this));
    }
    
    public function __toString() {
        return "UserServiceLink[linkId: ".$this->getLinkedId().", serviceId: $this->serviceId,  serviceUserId: $this->serviceUserId,  serviceDisplayName: $this->serviceDisplayName,  attributes: $this->attributes, ]";
    }
}
