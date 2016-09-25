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

namespace GW2Integration\Persistence\Helper;

use GW2Integration\Persistence\Persistence;
use PDO;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * Description of TeamspeakUserSessionPersistenceHelpter
 *
 * @author Jeppe Boysen Vennekilde
 */
class ServiceSessionPersistenceHelper {
    
    /**
     * 
     * @param type $session
     * @param type $expiration the time it takes in minutes for a session to expire
     * @return array() session data row if not expired
     */
    public static function getSessionIfStillValid($session, $expiration){
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'linked_user_sessions WHERE session = ? AND timestamp >= (NOW() - INTERVAL ? SECOND)';
        $queryParams = array(
            $session,
            $expiration
        );
        
        $sessionData = Persistence::getDBEngine()->prepare($preparedQueryString);

        $sessionData->execute($queryParams);
        
        $result = $sessionData->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @param string $session
     * @param string $sessionIp
     * @param integer $sessionTsDbid
     * @return type
     */
    public static function persistServiceSession($session, $serviceUserId, $serviceId, $sessionIp, $displayName = null, $isPrimary = true){ 
        global $gw2i_db_prefix;
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'linked_user_sessions (session, 	service_user_id, service_id, session_ip, is_primary'.($displayName != null ? ", service_display_name" : "").')
                VALUES(?,?,?,?,?'.($displayName != null ? ",?" : "").')';
        $queryParams = array(
            $session,
            $serviceUserId,
            $serviceId,
            $sessionIp,
            $isPrimary
        );
        if($displayName != null){
            $queryParams[] = $displayName;
        }
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        return $preparedStatement->execute($queryParams);
    }
    
    /**
     * 
     * @param int $olderThanInSeconds
     * @return type
     */
    public static function cleanupExpiredUserSessions($olderThanInSeconds){
        global $gw2i_db_prefix;
        $preparedQueryString = '
            DELETE FROM '.$gw2i_db_prefix.'linked_user_sessions WHERE timestamp < (NOW() - INTERVAL ? SECOND)';
        $queryParams = array(
            $olderThanInSeconds
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        return $preparedStatement->execute($queryParams);
    }
}
