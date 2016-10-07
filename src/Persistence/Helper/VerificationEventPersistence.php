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

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Persistence\Persistence;
use PDO;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of StatisticsPersistenceHelpter
 *
 * @author Jeppe Boysen Vennekilde
 */
class VerificationEventPersistence {
    
    const WORLD_MOVE_EVENT = 0; 
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $event
     * @param type $value
     * @return boolean
     */
    public static function persistVerificationEvent($linkedUser, $event, $value){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'verification_log (link_id, event, value)
                VALUES(?, ?, ?)';
        $queryParams = array(
            $linkId,
            $event,
            $value
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        return $preparedStatement->execute($queryParams);
    }
    
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param int[] $types
     * @param int $newerThan in seconds
     * @return array
     */
    public static function getVerificationEvents($types = null, $newerThan = null, $limit = 30, $offset = 0){
        global $gw2i_db_prefix;
        
        if(isset($types)){
            $inQuery = implode(',', array_fill(0, count($types), '?'));
        }
        
        if(isset($types)){
            $inQuery = implode(',', array_fill(0, count($types), '?'));
            $preparedQueryString = '
                SELECT * FROM '.$gw2i_db_prefix.'verification_log 
                WHERE type IN('.$inQuery.')' . (isset($newerThan) ? ' AND timestamp >= NOW() - INTERVAL ? SECOND' : "").' ORDER BY timestamp ASC, rid ASC LIMIT ? OFFSET ?';
        } else {
            $inQuery = implode(',', array_fill(0, count($types), '?'));
            $preparedQueryString = '
                SELECT * FROM '.$gw2i_db_prefix.'verification_log 
                ' . (isset($newerThan) ? 'WHERE timestamp >= NOW() - INTERVAL ? SECOND' : "").' ORDER BY timestamp DESC, rid ASC LIMIT ? OFFSET ?';
        }
        
        $queryParams = $types;
        
        if(isset($newerThan)){
            $queryParams[] = $newerThan;
        }
        
        $queryParams[] = $limit;
        $queryParams[] = $offset;
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $preparedStatement->execute($queryParams);
        
        return $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
}
