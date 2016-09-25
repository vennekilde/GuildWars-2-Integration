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

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Events\EventListener;
use GW2Integration\Events\EventManager;
use GW2Integration\Modules\Module;

/**
 * Description of ModuleLoader
 *
 * @author Jeppe Boysen Vennekilde
 */

abstract class AbstractVerificationModule extends Module implements EventListener{

    private $serviceId;

    public function __construct($moduleName, $serviceId) {
        parent::__construct($moduleName);
        $this->serviceId = $serviceId;
    }
    
    public function init() {
        ModuleLoader::addVerificationModule($this);
        EventManager::registerListener($this);
    }
    
    public abstract function handleUserWorldChanged(LinkedUser $linkedUser, $oldWorld, $newWorld);
    
    function getServiceId() {
        return $this->serviceId;
    }

}
