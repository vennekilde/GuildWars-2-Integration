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

namespace GW2Integration\Controller;

use GW2Integration\Persistence\Helper\ServiceSessionPersistenceHelper;
use GW2Integration\Utils\HashingUtils;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of TeamspeakUserSessionController
 *
 * @author Jeppe Boysen Vennekilde
 */
class ServiceSessionController {
    
    /**
     * 
     * @param type $sessionUserId
     * @param type $sessionId
     * @param type $sessionIp
     * @param type $displayName
     * @return type
     */
    public static function createServiceSession($sessionUserId, $sessionId, $sessionIp, $displayName, $isPrimary){
        $generateStrongHash = HashingUtils::generateStrongHash("$sessionUserId@$sessionId@$sessionIp@$isPrimary");
        //remove salt etc. don't need to verify the hash later, just need a random id
        $session = substr($generateStrongHash, 28);
        
        ServiceSessionPersistenceHelper::persistServiceSession($session, $sessionUserId, $sessionId, $sessionIp, $displayName, $isPrimary);
        
        return $session;
    }
    
    
    /**
     * 
     * @param type $session
     * @param type $expiration the time it takes in minutes for a session to expire
     * @return array() session data row if not expired
     */
    public static function getSessionIfStillValid($session, $expiration = null){
        if(!isset($expiration)){
            global $session_expiration_periode;
            $expiration = $session_expiration_periode;
        }
        return ServiceSessionPersistenceHelper::getSessionIfStillValid($session, $expiration);
    }
}
