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
namespace GW2Integration\LinkedServices;

use GW2Integration\Entity\LinkedUser;
/**
 * Description of LinkedService
 *
 * @author Jeppe Boysen Vennekilde
 */
abstract class LinkedService {

    private $serviceId;
    private $name;
    private $description;
    private $linkNotAvailable;
    private $canDetermineLinkDuringSetup;
    private $canCheckGroupId;
    private $hasConfigPage;
    
    public function __construct($serviceId, $name, $description, $linkNotAvailable, $canDetermineLinkDuringSetup, $canCheckGroupId, $hasConfigPage) {
        $this->serviceId = $serviceId;
        $this->name = $name;
        $this->description = $description;
        $this->linkNotAvailable = $linkNotAvailable;
        $this->canDetermineLinkDuringSetup = $canDetermineLinkDuringSetup;
        $this->canCheckGroupId = $canCheckGroupId;
        $this->hasConfigPage = $hasConfigPage;
    }
    
    public abstract function getAvailableUserServiceLink();
    
    public function getServiceId(){
        return $this->serviceId;
    }
    
    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getLinkNotAvailable() {
        return $this->linkNotAvailable;
    }
    
    public function canDetermineLinkDuringSetup(){
        return $this->canDetermineLinkDuringSetup;
    }
    
    /**
     * 
     * @param type $successJSFunction This JS function will be called when the has been succesfully set up the link
     * @return string
     */
    public abstract function getLinkSetupHTML($successJSFunction);
    
    /**
     * Check if the service is capable of checking if a user is within a certain group
     * @return type
     */
    public function canCheckGroupId(){
        return $this->canCheckGroupId;
    }
        
    public abstract function hasUserGroupId($serviceUserId, $groupId);
    
    function hasConfigPage() {
        return $this->hasConfigPage;
    }
    
    public abstract function getConfigPageHTML();
}
