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

namespace GW2Integration\Modules\Guilds\Persistence;

use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\Persistence\Persistence;
use PDO;

/**
 * Description of GuildPagesPersistence
 *
 * @author Jeppe Boysen Vennekilde
 */
class GuildPagesPersistence {
    
    
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param type $guildId
     * @return type
     */
    public static function getGuildPages(){
        global $gw2i_db_prefix;
        
        $pqs = 'SELECT * FROM '.$gw2i_db_prefix.'guild_pages gp
                INNER JOIN '.$gw2i_db_prefix.'guilds g ON g.g_uuid = gp.g_uuid
                ORDER BY g.g_tag';
        
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute();
        $result = $ps->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param type $guildId
     * @return type
     */
    public static function getGuildPage($guildId){
        global $gw2i_db_prefix;
        
        $pqs = 'SELECT * FROM '.$gw2i_db_prefix.'guild_pages gp
                INNER JOIN '.$gw2i_db_prefix.'guilds g ON g.g_uuid = gp.g_uuid
                WHERE gp.g_uuid = ? ORDER BY g.g_tag';
        $values = array(
            $guildId
        );
        
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
        $result =  $ps->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     */
    public static function getGuildsWithoutPage() {
        global $gw2i_db_prefix;

        $pqs = 'SELECT * FROM ' . $gw2i_db_prefix . 'guilds g '
                . 'LEFT JOIN ' . $gw2i_db_prefix . 'guild_pages gp ON gp.g_uuid = g.g_uuid '
                . 'WHERE gp.g_uuid IS NULL ORDER BY g.g_tag';
        
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute();
        $result =  $ps->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    
    /**
     * 
     * @global \GW2Integration\Persistence\Helper\String $gw2i_db_prefix
     * @param type $linkedUser
     * @param type $permission
     */
    public static function getGuildsWithoutPageWithUserPermission($linkedUser, $permission) {
        global $gw2i_db_prefix;
        $linkId = LinkingPersistencyHelper::determineLinkedUserId($linkedUser);

        $pqs = 'SELECT * FROM ' . $gw2i_db_prefix . 'guild_ranks gr '
                . 'INNER JOIN ' . $gw2i_db_prefix . 'guild_membership gm on gm.g_uuid = gr.g_uuid AND gm.g_rank = gr_name '
                . 'INNER JOIN ' . $gw2i_db_prefix . 'guilds g on gr.g_uuid = g.g_uuid '
                . 'LEFT JOIN ' . $gw2i_db_prefix . 'guild_pages gp on gm.g_uuid = gp.g_uuid '
                . 'WHERE gm.link_id = ? AND gp.g_uuid IS NULL AND FIND_IN_SET(?, gr.gr_permissions) > 0 ORDER BY g.g_tag';
        $params = array(
            $linkId,
            $permission
        );
        
        echo $pqs;
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($params);
        $result =  $ps->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param type $guildName
     * @return type
     */
    public static function getGuildPageByName($guildName){
        global $gw2i_db_prefix;
        
        $pqs = 'SELECT * FROM '.$gw2i_db_prefix.'guild_pages gp
                INNER JOIN '.$gw2i_db_prefix.'guilds g ON g.g_uuid = gp.g_uuid
                WHERE LOWER(g.g_name) = LOWER(?) ORDER BY g.g_tag';
        $values = array(
            $guildName
        );
        
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
        $result =  $ps->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    /**
     * 
     * @global \GW2Integration\Modules\Guilds\Persistence\type $gw2i_db_prefix
     * @param array $guildPageData
     * @return type
     */
    public static function persistGuildPage($guildPageData){
        global $gw2i_db_prefix;
        
        $vars = array_keys($guildPageData);
        $varsQuery = implode(",", $vars);
        $duplicateKeyQuery = "";
        foreach($vars AS $var){
            if($var != "g_uuid"){
                if(!empty($duplicateKeyQuery)){
                    $duplicateKeyQuery .= ",$var = VALUES($var)";
                } else {
                    $duplicateKeyQuery = "$var = VALUES($var)";
                }
            }
        }
        $valuesQuery = "?".str_repeat(",?", count($vars) - 1);
        
        $pqs = 'INSERT INTO '.$gw2i_db_prefix.'guild_pages ('.$varsQuery.')
                VALUES('.$valuesQuery.')
                ON DUPLICATE KEY UPDATE '.$duplicateKeyQuery;
        $values = array_values($guildPageData);
        
        $ps = Persistence::getDBEngine()->prepare($pqs);
        $ps->execute($values);
    }

}
