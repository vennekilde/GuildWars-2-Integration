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

namespace GW2Integration\Modules\Guilds;

use GW2Integration\Controller\AccessController;
use GW2Integration\Modules\Guilds\Persistence\GuildPagesPersistence;
use GW2Integration\Persistence\Helper\GW2DataPersistence;

/**
 * Description of GuildsControlelr
 *
 * @author Jeppe Boysen Vennekilde
 */
class GuildsPagesController {
    private static $ADMIN_RIGHT_PERM = "EditRoles";
    public static function canAdminGuild($linkedUser, $guildId){
        $canAdmin = AccessController::isAdmin();
        if(!$canAdmin){
            $canAdmin = GW2DataPersistence::hasGuildPermission($linkedUser, $guildId, self::$ADMIN_RIGHT_PERM);
        }
        return $canAdmin;
    }
    
    public static function getCanAdminGuilds($linkedUser){
        $canAdmin = AccessController::isAdmin();
        if($canAdmin){
            $guilds = GuildPagesPersistence::getGuildsWithoutPage();
        } else {
            $guilds = GuildPagesPersistence::getGuildsWithoutPageWithUserPermission($linkedUser, self::$ADMIN_RIGHT_PERM);
        }
        return $guilds;
    }
    
    public static function getGuildPagesByCategory(){
        $guildPages = GuildPagesPersistence::getGuildPages();
        $sortedGuildPages = array();
        foreach($guildPages AS $guildPage){
            $sortedGuildPages[$guildPage["g_category"]][] = $guildPage;
        }
        
        return $sortedGuildPages;
    }
    
    public static function getGuildPageCategoryFromId($categoryId){
        switch($categoryId){
            case 0:
                return "WvW Guilds";
            case 1:
                return "PvX Guilds";
            case 2:
                return "Roaming/Havoc";
            case 3:
                return "PvE Guilds";
            default:
                return "Unknown";
        }
    }
    
    public static function getGuildEmblemURL($guildId){
        return "//guilds.gw2w2w.com/$guildId.svg";
    }
}
