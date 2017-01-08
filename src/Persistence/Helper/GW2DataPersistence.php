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
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Entity\LinkIdHolder;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\GW2DataPrePersistEvent;
use GW2Integration\Exceptions\AccountAlreadyLinked;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Persistence;
use GW2Integration\Utils\GW2DataFieldConverter;
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

        $preparedQueryString = 'SELECT * FROM ' . $gw2i_db_prefix . 'accounts a WHERE a.link_id = ? LIMIT 1';

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

        $preparedQueryString = 'SELECT a.*, k.*, b.b_ban_id, b.b_reason AS ban_reason, b.b_banned_by, b.b_timestamp AS ban_timestamp, b.b_username '
                . 'FROM ' . $gw2i_db_prefix . 'accounts a '
                . 'INNER JOIN ' . $gw2i_db_prefix . 'api_keys k ON a.link_id = k.link_id '
                . 'LEFT JOIN ' . $gw2i_db_prefix . 'banned_accounts b ON UPPER(a.a_username) = UPPER(b.b_username)'
                . 'WHERE a.link_id = ? LIMIT 1';
        $values = array(
            $linkId
        );

        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute($values);
        $result = $preparedStatement->fetch(PDO::FETCH_ASSOC);

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

        foreach ($deleteQueries AS $deleteQuery) {
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
    public static function persistAccountData($linkedUser, $accountData, $createNewIfNotExists = false) {
        global $gw2i_db_prefix;
        try {
            $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);
        } catch (UnableToDetermineLinkId $e) {
            if (!$createNewIfNotExists) {
                throw $e;
            }
        }

        $preparedQueryString = '
            INSERT INTO ' . $gw2i_db_prefix . 'accounts (' . (isset($linkId) ? "link_id," : "") . 'a_uuid, a_username, a_world, a_created, a_access, a_commander, a_fractal_level, a_daily_ap, a_monthly_ap, a_wvw_rank)
                VALUES(' . (isset($linkId) ? ($linkId . ", ") : "") . ':a_uuid, :a_username, :a_world, :a_created, :a_access, :a_commander, :a_fractal_level, :a_daily_ap, :a_monthly_ap, :a_wvw_rank)
            ON DUPLICATE KEY UPDATE 
                link_id = LAST_INSERT_ID(link_id),
                a_uuid = VALUES(a_uuid),
                a_username = VALUES(a_username),
                a_world = VALUES(a_world),
                a_created = VALUES(a_created),
                a_access = VALUES(a_access),
                a_commander = VALUES(a_commander),
                a_fractal_level = VALUES(a_fractal_level),
                a_daily_ap = VALUES(a_daily_ap),
                a_monthly_ap = VALUES(a_monthly_ap),
                a_wvw_rank = VALUES(a_wvw_rank)';

        $values = array(
            ':a_uuid' => $accountData["id"],
            ':a_username' => $accountData["name"],
            ':a_world' => $accountData["world"],
            ':a_created' => $accountData["created"],
            ':a_access' => GW2DataFieldConverter::getAccountAccessIdFromString($accountData["access"]),
            ':a_commander' => $accountData["commander"] == "1",
        );

        //If true, that means the progression permission is given, so no need to check if the others is set
        if (isset($accountData["fractal_level"])) {
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

        try {
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
        if ($insertedId > 0 && $linkedUser instanceof LinkIdHolder) {
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
    public static function persistGuildMemberships($linkedUser, $guilds) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        //Remove no longer valid guild memberships
        $deleteOldGuildsPQS = '
                DELETE FROM ' . $gw2i_db_prefix . 'guild_membership
                WHERE link_id = ?
                    AND g_uuid NOT IN ("' . implode('","', $guilds) . '")';

        $values = array(
            $linkId
        );

        $deleteOldGuildsPS = Persistence::getDBEngine()->prepare($deleteOldGuildsPQS);
        $deleteOldGuildsPS->execute($values);

        //Insert new guilds
        $insertNewGuildsPQS = '
                INSERT IGNORE INTO ' . $gw2i_db_prefix . 'guild_membership(link_id, g_uuid)
                VALUES(?, ?)';

        $insertNewGuildsPS = Persistence::getDBEngine()->prepare($insertNewGuildsPQS);

        foreach ($guilds AS $guildUUID) {
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
     * @param array $guilds
     */
    public static function persistGuildMembershipWithRank($linkedUser, $guildId, $rank, $memberSince) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        $pqs = 'INSERT INTO ' . $gw2i_db_prefix . 'guild_membership(link_id, g_uuid, g_rank, g_member_since)
                    VALUES(?, ?, ? ,?)
                ON DUPLICATE KEY UPDATE 
                    g_rank = VALUES(g_rank),
                    g_member_since = VALUES(g_member_since)';

        $values = array(
            $linkId,
            $guildId,
            $rank,
            $memberSince
        );

        $pq = Persistence::getDBEngine()->prepare($pqs);
        $pq->execute($values);
    }

    /**
     * 
     * @global String $gw2i_db_prefix
     * @param LinkedUser $linkedUser
     */
    public static function deleteGuildMemberships($linkedUser) {
        global $gw2i_db_prefix;
        $linkIdQuery = LinkingPersistencyHelper::getLinkIdQuery($linkedUser);

        $deleteGuildMembershipsPQS = '
                DELETE FROM ' . $gw2i_db_prefix . 'guild_membership
                WHERE link_id = ' . $linkIdQuery;

        $deleteGuildMembershipsPS = Persistence::getDBEngine()->prepare($deleteGuildMembershipsPQS);
        $deleteGuildMembershipsPS->execute();
    }
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     * @param string $guildUUID
     * @param int[] $linkIds
     */
    public static function deleteGuildMembershipNotInList($guildUUID, $linkIds) {
        global $gw2i_db_prefix;

        $params = array($guildUUID);
        $linkIdsQueryPart = "";
        foreach($linkIds AS $index => $linkId){
            $params[] = $linkId;
            if($index == 0){
                $linkIdsQueryPart .= " AND link_id NOT IN(?";
            } else {
                $linkIdsQueryPart .= ",?";
            }
        }
        if(!empty($linkIdsQueryPart)){
            $linkIdsQueryPart .= ")";
        }
        
        $deleteGuildMembershipsPQS = '
                DELETE FROM ' . $gw2i_db_prefix . 'guild_membership
                WHERE g_uuid = ?' . $linkIdsQueryPart;

        $deleteGuildMembershipsPS = Persistence::getDBEngine()->prepare($deleteGuildMembershipsPQS);
        $deleteGuildMembershipsPS->execute($params);
    }

    
    
    /**
     * 
     * @global String $gw2i_db_prefix
     * @param LinkedUser $linkedUser
     * @return array
     */
    public static function getGuildMembershipWithGuildsData($linkedUser) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        $pqs = 'SELECT membership.*, guilds.g_name, guilds.g_tag '
                . 'FROM ' . $gw2i_db_prefix . 'guild_membership AS membership '
                . 'LEFT JOIN ' . $gw2i_db_prefix . 'guilds AS guilds on membership.g_uuid = guilds.g_uuid '
                . 'WHERE link_id = ? ORDER BY g_tag';
        $values = array(
            $linkId
        );

        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
        $result = $ps->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * 
     * @global String $gw2i_db_prefix
     * @param string $guildId
     * @param boolean $ignoreOlderThan
     * @return array
     */
    public static function getGuildMembers($guildId, $ignoreOlderThan = false) {
        global $gw2i_db_prefix;
        
        $values = array(
            $guildId
        );
        if($ignoreOlderThan){
            $values[] = $ignoreOlderThan;
        }
        $pqs = 'SELECT * '
                . 'FROM ' . $gw2i_db_prefix . 'guild_membership AS g '
                . 'LEFT JOIN ' . $gw2i_db_prefix . 'accounts AS a on a.link_id = g.link_id '
                . ($ignoreOlderThan ? 'LEFT JOIN ' . $gw2i_db_prefix . 'api_keys AS k ON k.link_id = a.link_id ' : "")
                . 'WHERE g.g_uuid = ? '.($ignoreOlderThan ? 'AND (k.last_success > k.last_attempted_fetch - INTERVAL ? SECOND OR k.link_id IS NULL OR g_rank IS NOT NULL)' : "").' ORDER BY a.a_username';

        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
        $result = $ps->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * 
     * @global String $gw2i_db_prefix
     * @param LinkedUser $linkedUser
     * @param array $guilds
     */
    public static function getGuildMemberships($linkedUser) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        $pqs = 'SELECT * '
                . 'FROM ' . $gw2i_db_prefix . 'guild_membership AS g '
                . 'WHERE g.link_id = ?';
        $values = array(
            $linkId
        );

        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
        $result = $ps->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Of the provided guild ids, return those id's that has not been synched, or hasn't
     * been synched in a while
     * @param array $guildIds
     * @param boolean $checkLastSynched
     * @return array
     */
    public static function getGuildsAlreadySynched($guildIds, $checkLastSynched = true) {
        global $gw2i_db_prefix, $gw2i_refresh_guild_data_interval;
        if ($checkLastSynched) {
            $addonQuery = 'WHERE g_last_synched > NOW() - INTERVAL ? SECOND AND g_uuid IN("' . implode('","', $guildIds) . '")';
            $params = array($gw2i_refresh_guild_data_interval);
        } else {
            $addonQuery = 'WHERE g_uuid IN("' . implode('","', $guildIds) . '")';
            $params = array();
        }
        $pqs = 'SELECT g_uuid FROM ' . $gw2i_db_prefix . 'guilds ' . $addonQuery;
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
        $guildsAlreadySynched = $ps->fetch(PDO::FETCH_NUM);

        return $guildsAlreadySynched;
    }

    /**
     * Persist details about a guild in the database
     * @param type $guildDetails
     */
    public static function persistGuildDetails($guildDetails) {
        global $gw2i_db_prefix;

        $pqs = 'INSERT INTO ' . $gw2i_db_prefix . 'guilds (g_uuid, g_name, g_tag) '
                . 'VALUES(?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE '
                . 'g_name = VALUES(g_name), '
                . 'g_tag  = VALUES(g_tag)';
        $params = array(
            $guildDetails["guild_id"],
            $guildDetails["guild_name"],
            $guildDetails["tag"]
        );
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
    }

    /**
     * Persist ranks for a guild in the database
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     * @param type $guildUUID
     * @param type $rankName
     * @param type $order
     * @param type $permissions
     * @param type $icon
     */
    public static function persistGuildRanks($guildUUID, $rankName, $order, $permissions, $icon) {
        global $gw2i_db_prefix;

        $pqs = 'INSERT INTO ' . $gw2i_db_prefix . 'guild_ranks (g_uuid, gr_name, gr_order, gr_permissions, gr_icon) '
                . 'VALUES(?, ?, ?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE '
                . 'gr_name  = VALUES(gr_name),'
                . 'gr_order  = VALUES(gr_order),'
                . 'gr_permissions  = VALUES(gr_permissions),'
                . 'gr_icon  = VALUES(gr_icon)';
        $params = array(
            $guildUUID,
            $rankName,
            $order,
            implode(",", $permissions),
            $icon
        );
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
    }

    /**
     * 
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     * @param type $guildUUID
     * @param type $permission
     */
    public static function getGuildRanksWithPermission($guildUUID, $permission) {
        global $gw2i_db_prefix;

        $pqs = 'SELECT * FROM ' . $gw2i_db_prefix . 'guild_ranks gr '
                . 'WHERE AND gr.g_uuid = ? AND ? IN(gr.gr_permissions)';
        $params = array(
            $guildUUID,
            $permission
        );
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
        
        return $ps->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     * @param type $linkedUser
     * @param type $guildUUID
     * @param type $permission
     */
    public static function hasGuildPermission($linkedUser, $guildUUID, $permission) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        $pqs = 'SELECT 1 FROM ' . $gw2i_db_prefix . 'guild_ranks gr '
                . 'INNER JOIN ' . $gw2i_db_prefix . 'guild_membership gm on gm.g_uuid = gr.g_uuid AND gm.g_rank = gr_name '
                . 'WHERE gm.link_id = ? AND gr.g_uuid = ? AND FIND_IN_SET(?, gr.gr_permissions) > 0';
        $params = array(
            $linkId,
            $guildUUID,
            $permission
        );
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
        return $ps->rowCount() > 0;
    }
    
    
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     * @param type $linkedUser
     * @param type $permission
     */
    public static function getGuildsWithUserPermission($linkedUser, $permission) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        $pqs = 'SELECT * FROM ' . $gw2i_db_prefix . 'guild_ranks gr '
                . 'INNER JOIN ' . $gw2i_db_prefix . 'guild_membership gm on gm.g_uuid = gr.g_uuid AND gm.g_rank = gr_name '
                . 'WHERE gm.link_id = ? AND FIND_IN_SET(?, gr.gr_permissions) > 0';
        $params = array(
            $linkId,
            $permission
        );
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
        return $ps->fetchAll(PDO::FETCH_ASSOC);
    }

    
    
    /**
     * 
     * @param array $values
     */
    public static function persistCharacterData($linkedUser, $character) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);


        $values = array(
            'link_id' => $linkId,
            'c_name' => $character["name"],
            'c_race' => $this->getRaceIdFromString($character["race"]),
            'c_gender' => $this->getGenderIdFromString($character["gender"]),
            'c_profession' => $this->getProfessionIdFromString($character["profession"]),
            'c_level' => $character["level"],
            'c_age' => $character["age"],
            'c_created' => $character["created"],
            'c_deaths' => $character["deaths"]
        );
        if (isset($character["title"])) {
            $values['g_uuid'] = $character["guild"];
        }
        if (isset($character["title"])) {
            $values['c_title'] = $character["title"];
        }

        $pqs = 'INSERT INTO ' . $gw2i_db_prefix . 'gw2integration_characters (g_uuid, g_name, g_tag) '
                . 'VALUES(?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE '
                . 'g_name = VALUES(g_name), '
                . 'g_tag  = VALUES(g_tag)';
        \IPS\Db::i()->replace('gw2integration_characters', $values, array("c_name"));
    }

    public static function deleteCharacterData($userId) {
        \IPS\Db::i()->query('DELETE c, cr FROM gw2integration_characters c 
            INNER JOIN gw2integration_character_crafting cr ON c.c_name = cr.c_name
            WHERE c.u_id = ' . intval($userId));
    }

    public static function getCharactersData($userId) {
        $dbPrefix = \IPS\Db::i()->prefix;
        return \IPS\Db::i()->query(
                        'SELECT * '
                        . 'FROM ' . $dbPrefix . 'gw2integration_characters AS c '
                        . 'LEFT JOIN ' . $dbPrefix . 'gw2integration_guilds AS g on g.g_uuid = c.g_uuid '
                        . 'WHERE c.u_id = ' . intval($userId) . ' ORDER BY c_age DESC'
        );
    }

    public static function getCharacterData($characterName) {
        try {
            $dbPrefix = \IPS\Db::i()->prefix;
            return \IPS\Db::i()->query(
                            'SELECT * '
                            . 'FROM ' . $dbPrefix . 'gw2integration_characters AS c '
                            . 'LEFT JOIN ' . $dbPrefix . 'gw2integration_guilds AS g on g.g_uuid = c.g_uuid '
                            . 'WHERE c.c_name = ' . addslashes($characterName) . ' LIMIT 1'
            );
        } catch (UnderflowException $e) {
            $result = null;
        }
        return $result;
    }

    /**
     * 
     * @param string $characterName
     * @param arrau $craftingProfessionValues
     */
    public static function persistCharacterCraftingProfessions($characterName, $craftingProfessionValues) {
        foreach ($craftingProfessionValues AS $craftingProfessionData) {
            //\IPS\Session::i()->log(null,  "craftingProfessionValues: " . json_encode($craftingProfessionData));
            $values = array(
                "c_name" => $characterName,
                "cr_discipline" => $this->getCraftingProfIdFromString($craftingProfessionData["discipline"]),
                "cr_rating" => $craftingProfessionData["rating"],
                "cr_active" => $craftingProfessionData["active"],
            );
            $this->persistCharacterCraftingProfession($values);
        }
    }

    /**
     * 
     * @param array $values
     */
    public static function persistCharacterCraftingProfession($values) {
        \IPS\Db::i()->replace('gw2integration_character_crafting', $values, array("c_name"));
    }

    public static function getCharacterCraftingProfessions($characterName) {
        return \IPS\Db::i()->select('*', 'gw2integration_character_crafting', array("c_name = ?", $characterName));
    }

}
