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

use GW2Integration\Controller\GW2DataController;
use GW2Integration\Utils\GW2DataFieldConverter;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\GW2DataPrePersistEvent;
use GW2Integration\Exceptions\AccountAlreadyLinked;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Persistence;
use PDO;
use PDOException;
use UnderflowException;

/**
 * Description of GW2DataPersistence
 *
 * @author Jeppe Boysen Vennekilde
 */
class GW2DataPersistence {
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function getAccountData($linkedUser) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = 'SELECT * FROM '.$gw2i_db_prefix.'accounts a WHERE a.link_id = ? LIMIT 1';

        $queryParams = array(
            $linkId
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($queryParams);
        $result = $preparedStatement->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * Combines data from
     * - Accounts
     * - API Keys
     * - Bans
     * @param LinkedUser $linkedUser
     */
    public static function getExtensiveAccountData($linkedUser) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $preparedQueryString = 
                'SELECT a.*, k.*, b.b_ban_id, b.b_reason, b.b_banned_by, b.b_timestamp, b.b_username '
                . 'FROM '.$gw2i_db_prefix.'accounts a '
                . 'INNER JOIN '.$gw2i_db_prefix.'api_keys k ON a.link_id = k.link_id '
                . 'LEFT JOIN '.$gw2i_db_prefix.'banned_accounts b ON UPPER(a.a_username) = UPPER(b.b_username)'
                . 'WHERE a.link_id = ? LIMIT 1';
        $values = array(
            $linkId
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($values);
        $result =  $preparedStatement->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    
    /**
     * 
     * @param LinkedUser|UserServiceLink|integer $identifier
     */
    public static function deleteAllData($identifier) {
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($identifier);
        
        $deleteQueries = array(
            "DELETE FROM gw2integration_api_keys WHERE link_id = ?",
            "DELETE FROM gw2integration_accounts WHERE link_id = ?"
        );
        
        $values = array($linkId);
        
        foreach($deleteQueries AS $deleteQuery){
            $preparedStatement = Persistence::getDBEngine()->prepare($deleteQuery);
            $preparedStatement->execute($values);
        }
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $accountData
     * @param boolean $createNewIfNotExists
     * @return boolean
     */
    public static function persistAccountData($linkedUser, $accountData, $createNewIfNotExists = false){
        global $gw2i_db_prefix, $logger;
        try{
            $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
            
            $logger->info($linkId);
        }catch (UnableToDetermineLinkId $e){
            if(!$createNewIfNotExists){
                throw $e;
            }
        }
        
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'accounts (' . (isset($linkId) ? "link_id," : "") . 'a_uuid, a_username, a_world, a_created, a_access, a_commander, a_fractal_level, a_daily_ap, a_monthly_ap, a_wvw_rank)
                VALUES(' . (isset($linkId) ? ($linkId . ", ") : "") . ':a_uuid, :a_username, :a_world, :a_created, :a_access, :a_commander, :a_fractal_level, :a_daily_ap, :a_monthly_ap, :a_wvw_rank)
            ON DUPLICATE KEY UPDATE 
                a_world = VALUES(a_world),
                a_created = VALUES(a_created),
                a_access = VALUES(a_access),
                a_commander = VALUES(a_commander),
                a_fractal_level = VALUES(a_fractal_level),
                a_daily_ap = VALUES(a_daily_ap),
                a_monthly_ap = VALUES(a_monthly_ap),
                a_wvw_rank = VALUES(a_wvw_rank)';
        
        $values = array(
            ':a_uuid'       => $accountData["id"],
            ':a_username'   => $accountData["name"],
            ':a_world'      => $accountData["world"],
            ':a_created'    => $accountData["created"],
            ':a_access'     => GW2DataFieldConverter::getAccountAccessIdFromString($accountData["access"]),
            ':a_commander'  => $accountData["commander"] == "1",
        );
        
        //If true, that means the progression permission is given, so no need to check if the others is set
        if(isset($accountData["fractal_level"])){
            $values[":a_fractal_level"] = $accountData["fractal_level"];
            $values[":a_daily_ap"] = $accountData["daily_ap"];
            $values[":a_monthly_ap"] = $accountData["monthly_ap"];
            $values[":a_wvw_rank"] = $accountData["wvw_rank"];
        } else {
            $values[":a_fractal_level"] = 0;
            $values[":a_daily_ap"] = 0;
            $values[":a_monthly_ap"] = 0;
            $values[":a_wvw_rank"] = 0;
        }
        
        //Let listeners perform data manipulation if needed before persisting
        $event = new GW2DataPrePersistEvent($linkedUser, "v2/account", $values);
        EventManager::fireEvent($event);
        
        //Persist account data
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        
        try{
            $result = $preparedStatement->execute($values);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
               // duplicate entry, do something else
                throw new AccountAlreadyLinked($values[":a_username"], $e->getMessage());
            } else {
               // an error other than duplicate entry occurred
                throw $e;
            }
         }
        
        //Get inserted id. Account data holds the primary link_id table
        $insertedId = Persistence::getDBEngine()->lastInsertId();
        if($insertedId > 0){
            //Update linkId with the inserted id
            $linkedUser->setLinkedId($insertedId);
        }
        
        return $result;
    }
        
    /**
     * 
     * @global String $gw2i_db_prefix
     * @param LinkedUser $linkedUser
     * @param array $guilds
     */
    public static function persistGuildMemberships($linkedUser, $guilds){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        //Remove no longer valid guild memberships
        $deleteOldGuildsPQS = '
                DELETE FROM '.$gw2i_db_prefix.'guild_membership
                WHERE link_id = ?
                    AND g_uuid NOT IN ("' . implode('","',$guilds) . '")';
        
        $values = array(
            $linkId
        );
        
        $deleteOldGuildsPS = Persistence::getDBEngine()->prepare($deleteOldGuildsPQS);
        $deleteOldGuildsPS->execute($values);
        
        //Insert new guilds
        $insertNewGuildsPQS = '
                INSERT IGNORE INTO '.$gw2i_db_prefix.'guild_membership(link_id, g_uuid)
                VALUES(?, ?)';

        $insertNewGuildsPS = Persistence::getDBEngine()->prepare($insertNewGuildsPQS);
        
        foreach($guilds AS $guildUUID){
            $values[1] = $guildUUID;
            $insertNewGuildsPS->execute($values);
        }
        
        //Retrieve guild details
        //Circular dependicies, i know, it is bad, it is late, i don't care atm
        GW2DataController::fetchGuildsData($guilds);
    }
    
    /**
     * 
     * @global String $gw2i_db_prefix
     * @param LinkedUser $linkedUser
     */
    public static function deleteGuildMemberships($linkedUser){
        global $gw2i_db_prefix;
        $linkIdQuery = LinkingPersistencyHelper::getLinkIdQuery($linkedUser);
        
        $deleteGuildMembershipsPQS = '
                DELETE FROM '.$gw2i_db_prefix.'guild_membership
                WHERE link_id = ' . $linkIdQuery;
        
        $deleteGuildMembershipsPS = Persistence::getDBEngine()->prepare($deleteGuildMembershipsPQS);
        $deleteGuildMembershipsPS->execute();
    }
    
    /**
     * 
     * @global String $gw2i_db_prefix
     * @param LinkedUser $linkedUser
     * @return array
     */
    public static function getGuildMembershipWithGuildsData($linkedUser){
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        
        $pqs = 'SELECT membership.*, guilds.g_name, guilds.g_tag '
                . 'FROM '.$gw2i_db_prefix.'guild_membership AS membership '
                . 'LEFT JOIN '.$gw2i_db_prefix.'guilds AS guilds on membership.g_uuid = guilds.g_uuid ' 
                . 'WHERE link_id = ? ORDER BY g_tag';
        $values = array(
            $linkId
        );
        
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
        $result =  $ps->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * Of the provided guild ids, return those id's that has not been synched, or hasn't
     * been synched in a while
     * @param array $guildIds
     * @param boolean $checkLastSynched
     * @return array
     */
    public static function getGuildsAlreadySynched($guildIds, $checkLastSynched = true){
        global $gw2i_db_prefix, $gw2i_refresh_guild_data_interval;
        if($checkLastSynched){
            $addonQuery = 'WHERE g_last_synched > NOW() - INTERVAL ? SECOND AND g_uuid IN("' . implode('","', $guildIds) . '")';
            $params = array($gw2i_refresh_guild_data_interval);
        } else {
            $addonQuery = 'WHERE g_uuid IN("' . implode('","', $guildIds) . '")';
            $params = array();
        }
        $pqs = 'SELECT g_uuid FROM '.$gw2i_db_prefix.'guilds ' . $addonQuery;
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
        $guildsAlreadySynched = $ps->fetchAll(PDO::FETCH_COLUMN, 0);
        
        return $guildsAlreadySynched;
    }
    
    /**
     * Persist details about a guild in the database
     * @param type $guildDetails
     */
    public static function persistGuildDetails($guildDetails){
        global $gw2i_db_prefix;
        
        $pqs = 'INSERT INTO '.$gw2i_db_prefix.'guilds (g_uuid, g_name, g_tag, g_last_synched) '
                . 'VALUES(?, ?, ?, CURRENT_TIMESTAMP) '
                . 'ON DUPLICATE KEY UPDATE '
                    . 'g_tag  = VALUES(g_tag), '
                    . 'g_last_synched  = VALUES(g_last_synched) ';
        $params = array(
            $guildDetails["guild_id"], 
            $guildDetails["guild_name"],
            $guildDetails["tag"]
        );
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
    }
}
