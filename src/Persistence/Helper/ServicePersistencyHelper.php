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
 * Description of StatisticsPersistenceHelpter
 *
 * @author Jeppe Boysen Vennekilde
 */
class ServicePersistencyHelper {
    
    /**
     * 
     * 
     * @global type $gw2i_db_prefix
     * @return array
     */
    public static function getWorldToGroupSettings() {
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'world_to_service_group';

        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute();
        
        $result = $preparedStatement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\type $gw2i_db_prefix
     * @param type $world
     * @param type $serviceId
     * @param type $groupId
     * @param type $isPrimary
     * @return type
     */
    public static function persistWorldToGroupSettings($world, $serviceId, $isPrimary, $groupId){
        global $gw2i_db_prefix;
        
        $queryParams = array(
            $world,
            $serviceId,
            $groupId,
            $isPrimary
        );
        
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'world_to_service_group (world, service_id, group_id, is_primary)
                VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE 
                group_id = VALUES(group_id)';
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $result = $preparedStatement->execute($queryParams);
        return $result;
    }
    
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\type $gw2i_db_prefix
     * @param type $world
     * @param type $serviceId
     * @param type $isPrimary
     * @param type $groupId
     * @return type
     */
    public static function removeWorldToGroupSettings($world, $serviceId, $isPrimary, $groupId = null){
        global $gw2i_db_prefix;
        
        $queryParams = array(
            $world,
            $serviceId,
            $isPrimary
        );
        if(isset($groupId)){
            $queryParams[] = $groupId;
        }
        
        $preparedQueryString = '
            DELETE FROM '.$gw2i_db_prefix.'world_to_service_group '
                . 'WHERE world = ? AND service_id = ? AND is_primary = ?' 
                . (isset($groupId) ? ' AND group_id = ?' : '');
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $result = $preparedStatement->execute($queryParams);
        return $result;
    }
}
