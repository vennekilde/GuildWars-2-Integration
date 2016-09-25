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

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Persistence\Helper\APIKeyPersistenceHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of GW2AccountDataController
 *
 * @author Jeppe Boysen Vennekilde
 */
class GW2TokenInfoController {
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @param array $tokenInfo
     */
    public static function processTokenInfo($linkedUser, $apiKey, $tokenInfo){
        return APIKeyPersistenceHelper::persistTokenInfo($linkedUser, $apiKey, $tokenInfo);
    }
    
    
    /**
     * Calculate the amount of time in seconds left before an API Key expires, given its last
     * successful fetch
     * @global type $expiration_grace_periode
     * @param type $timestamp
     * @return type
     */
    public static function calculateTimeLeftBeforeKeyExpires($timestamp) {
        global $expiration_grace_periode;
        return strtotime($timestamp) + $expiration_grace_periode - time();
    }

    /**
     * Calculate the amount of time in seconds left before a users temporary 
     * access expires, given the time it was given
     * @global type $expiration_grace_periode
     * @param type $timestamp
     * @return type
     */
    public static function calculateTimeLeftBeforeTemporaryAccessExpires($timestamp) {
        return strtotime($timestamp) + SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::TEMPORARY_ACCESS_EXPIRATION) - time();
    }
}
