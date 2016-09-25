<?php

namespace GW2Integration\Logger;

use GW2Integration\Events\EventListener;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\Events\Events\UserServiceLinkEvent;

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

/**
 * Description of EventLogger
 *
 * @author Jeppe Boysen Vennekilde
 */
class EventLogger implements EventListener{
    public function __construct() {
        EventManager::registerListener($this);
    }
    /**
     * 
     * @global type $logger
     * @param UserServiceLinkEvent $event
     */
    public function onUserServiceLinkEvent(UserServiceLinkEvent $event){
        global $logger;
        $logger->info($event);
    }
    
    
    public function onAPISyncCompleted(APISyncCompleted $event){
        global $logger;
        $logger->debug("API Sync Completed");
    }
}
