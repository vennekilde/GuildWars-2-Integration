<?php

use GW2Integration\Controller\AccessController;
use GW2Integration\Modules\Guilds\GuildsPagesController;
use GW2Integration\Persistence\Helper\APIKeyPersistenceHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\REST\RESTHelper;
/*
 * The MIT License
 *
 * Copyright 2016 venne.
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

$linkedUser = RESTHelper::getLinkedUserFromParams();
?>
<div style="padding: 0px 10px 20px 10px">
    <h3 style="margin-top: 10px">How to get a Guild Page</h3>
    <div style="white-space: pre-line">So you want a guild page of your very own?
        The guild pages has been integrated with the GuildWars 2 REST API, allowing much easier creation and management of guild pages than before
        
        Here is a step by step guide on how you can add your guild
    </div>
    <ol class="help_list" style="margin-top: 0;">
        <li>
            <b>Setup API Key</b>
            <ol type="A">
                <li><i>Are you the <b>Guild Leader?</b></i></li>
                You need to have an active API key with the <b>Guilds</b> permission.
                <?php                
                    $keyData = APIKeyPersistenceHelper::getAPIKey($linkedUser);
                    if(in_array("guilds", explode(",", $keyData["api_key_permissions"]))){
                        echo "<div style='color: green'>Your API Key already has this permission</div>";
                    } else {
                        echo "<div style='font-style: italic'>Your API Key <b style='color: red'>DOES NOT</b> have this permission. You can change your API Key <a target='_top' href='".SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::SETUP_WEB_PATH)."'>here</a></div>";
                    }
                ?>
                <li><i>Are you a <b>Guild Officer?</b></i></li>
                Ensure your Guild leader has at least gone through step 1A.
            </ol>
        </li>
        <li><b>Select the Guild you want added from the list below</b></li>
        This list will only show guilds without a page already and you also have the guild right to edit roles
        <br />
        <select name="guild_list">
            <option selected disabled>None</option>
        <?php
            $guilds = GuildsPagesController::getCanAdminGuilds($linkedUser);
            foreach($guilds AS $guild){
               echo '<option value="'.$guild["g_uuid"].'">['.$guild["g_tag"].'] '.$guild["g_name"].'</option>'; 
            }
        ?>
        </select>
        <br /><button name="form" value="request-page" class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect" style="margin-top: 10px;">Request Page</button>
        <?php 
            if(AccessController::isAdmin()){
                echo "<div style='font-style: italic;padding-top: 10px;'>Note that you are an Admin and can therefor see every guild registered to any member</div>";
            }
        ?>
        <li><b>Await Admin approval</b></li>
        The Admin team will be notified, once a guild page request has been submitted.
    </ol>
</div>