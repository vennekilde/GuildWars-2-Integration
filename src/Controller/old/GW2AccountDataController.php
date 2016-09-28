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
use GW2Integration\Exceptions\AccountUsernameBanned;
use GW2Integration\Exceptions\UserAlreadyLinkedException;
use GW2Integration\Modules\Guilds\ModuleLoader;
use GW2Integration\Modules\Verification\VerificationController;
use GW2Integration\Persistence\Helper\BannedGW2AccountPersistencyHelper;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\VerificationEventPersistence;
use PDOException;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * Description of GW2AccountDataController
 *
 * @author Jeppe Boysen Vennekilde
 */
class GW2AccountDataController {

    public static $worldChangedListeners = array();

    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $accountData
     */
    public static function processAccountData($linkedUser, $accountData) {
        $isBanned = BannedGW2AccountPersistencyHelper::isAccountBanned($accountData["name"]);
        if ($isBanned) {
            global $worldToForumUserGroup;
            removeUserFromForumUserGroup($linkedUser, $worldToForumUserGroup[$accountData["world"]]);
            throw new AccountUsernameBanned($accountData);
        }

        //Get old account data
        $persistedAccountData = GW2DataPersistence::getAccountData($linkedUser);

        $result = false;
        //Persist new account data
        try {
            $result = GW2DataPersistence::persistAccountData($linkedUser, $accountData);
        } catch (PDOException $ex) {
            echo $ex->errorInfo[0];
            if ($ex->errorInfo[0] == 23000) {
                throw new UserAlreadyLinkedException();
            } else {
                throw $ex;
            }
        }

        $oldWorld = $persistedAccountData === false ? -1 : $persistedAccountData["world"];
        //Determine if world loyality changed
        static::checkWorldChanged($linkedUser, $accountData, $oldWorld, $accountData["world"]);

        //Process individual guild memberships
        static::processGuildsAccountData($linkedUser, $accountData["guilds"], $accountData["world"]);

        return $result;
    }

    /**
     * 
     * @global type $worldToForumUserGroup
     * @param LinkedUser $linkedUser
     * @param type $oldWorld
     * @param type $newWorld
     */
    public static function checkWorldChanged($linkedUser, $accountData, $oldWorld, $newWorld) {
        if ($oldWorld != $newWorld) {
            VerificationEventPersistence::persistVerificationEvent($linkedUser, 0, $oldWorld . "," . $newWorld);

            VerificationController::onAccessStatusChanged($linkedUser, $accountData);
            //Remove from old world group
            foreach (static::$worldChangedListeners AS $listener) {
                call_user_func($listener, $linkedUser, $oldWorld, $newWorld);
            }
        }
    }

    /**
     * 
     * @param LinkedUser $linkedUser
     * @param array(string) $guildIds 
     */
    public static function processGuildsAccountData($linkedUser, $guildIds, $world) {
        //Temporary, using V2 code
        if($linkedUser->getPrimaryUserServiceLinks()[0]->getServiceUserId() != null){
            ModuleLoader::updateGuildAssociation($linkedUser->getPrimaryUserServiceLinks()[0]->getServiceUserId(), $guildIds, $world);
        }
    }

}
