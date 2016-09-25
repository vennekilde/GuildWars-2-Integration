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
class AccountPersistenceHelper {
    
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function getAccountData($linkedUser) {
        $userIdentification = LinkingPersistencyHelper::getUserIdColumnAndValue($linkedUser);
        
        if($userIdentification === null){
            return false;
        }
        $preparedQueryString = 'SELECT * FROM gw2_accounts INNER JOIN gw2_api_keys ON gw2_accounts.link_id = gw2_api_keys.link_id '.$userIdentification[0].' LIMIT 1';

        $queryParams = (array) $userIdentification[1];
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($queryParams);
        $result = $preparedStatement->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function getAccountAndAPIKeyData($linkedUser) {
        $userIdentification = LinkingPersistencyHelper::getUserIdColumnAndValue($linkedUser);
        if($userIdentification === null){
            return false;
        }
        
        $preparedQueryString = 
                'SELECT gw2_accounts.*, gw2_api_keys.*, gw2_banned_accounts.ban_id, gw2_banned_accounts.reason AS ban_reason, gw2_banned_accounts.banned_by, gw2_banned_accounts.timestamp AS ban_timestamp '
                . 'FROM gw2_accounts '
                . 'INNER JOIN gw2_api_keys ON gw2_accounts.link_id = gw2_api_keys.link_id '
                . 'LEFT JOIN gw2_banned_accounts ON UPPER(gw2_accounts.username) = UPPER(gw2_banned_accounts.username)'
                .$userIdentification[0].' LIMIT 1';
        $queryParams = (array) $userIdentification[1];
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($queryParams);
        $result =  $preparedStatement->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $accountData
     * @return boolean
     */
    public static function persistAccountData($linkedUser, $accountData){
        $linkIdQuery = LinkingPersistencyHelper::getLinkIdQuery($linkedUser);
        
        $preparedQueryString = '
            INSERT INTO gw2_accounts (link_id, uuid, username, world, created, access, commander, fractal_level, daily_ap, monthly_ap, wvw_rank)
                VALUES(' . $linkIdQuery . ',?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE 
                link_id = LAST_INSERT_ID(link_id),
                world = VALUES(world),
                created = VALUES(created),
                access = VALUES(access),
                commander = VALUES(commander),
                fractal_level = VALUES(fractal_level),
                daily_ap = VALUES(daily_ap),
                monthly_ap = VALUES(monthly_ap),
                wvw_rank = VALUES(wvw_rank)';
        
        $queryParams = array(
            $accountData["id"],
            $accountData["name"],
            $accountData["world"],
            $accountData["created"],
            static::parseAccessString($accountData["access"]),
            isset($accountData["commander"]) ? $accountData["commander"] : 0,
            isset($accountData["fractal_level"]) ? $accountData["fractal_level"] : 0,
            isset($accountData["daily_ap"]) ? $accountData["daily_ap"] : 0,
            isset($accountData["monthly_ap"]) ? $accountData["monthly_ap"] : 0,
            isset($accountData["wvw_rank"]) ? $accountData["wvw_rank"] : 0
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $result = $preparedStatement->execute($queryParams);
        
        $insertedId = Persistence::getDBEngine()->lastInsertId();
        if($insertedId > 0){
            $linkedUser->setLinkedId($insertedId);
        }
        
        return $result;
    }
    
    public static function parseAccessString($accessString){
        switch($accessString){
            case "GuildWars2":
                return 0;
            case "HeartOfThorns":
                return 1;
            case "PlayForFree":
                return 2;
            case "None":
                return 3;
        }
        return -1;
    }
}
