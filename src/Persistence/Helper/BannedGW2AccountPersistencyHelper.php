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
 * Description of GW2LinkingPersistencyHelper
 *
 * @author Jeppe Boysen Vennekilde
 */
class BannedGW2AccountPersistencyHelper {
    
    /**
     * 
     */
    public static function getBannedAccounts() {
        $preparedQueryString = 'SELECT * FROM gw2_banned_accounts';
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute();
        return $preparedStatement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * @param String $accountName
     * @return boolean
     */
    public static function isAccountBanned($accountName) {
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'banned_accounts WHERE b_username = ? LIMIT 1';
        $queryParams = array($accountName);
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($queryParams);
        return $preparedStatement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 
     * @param String $accountName
     * @param String $reason
     * @param LinkedUser $bannedBy
     * @return boolean
     */
    public static function banAccount($accountName, $reason, $bannedBy){
        $preparedQueryString = '
            INSERT INTO gw2_accounts (username, reason, banned_by)
                VALUES(?,?,?)
            ON DUPLICATE KEY UPDATE 
                reason = VALUES(reason),
                banned_by = VALUES(banned_by)';
        $queryParams = array(
            $accountName,
            $reason,
            $bannedBy
        );
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        $result = $preparedStatement->execute($queryParams);
        return $result;
    }
}
