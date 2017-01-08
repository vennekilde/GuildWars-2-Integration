<?php

use GW2Integration\Modules\Guilds\GuildsPagesController;
use GW2Integration\Modules\Guilds\Persistence\GuildPagesPersistence;
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

/**
 * Description of GuildPageController
 *
 * @author venne
 */
$linkedUser = RESTHelper::getLinkedUserFromParams();
$canAdminGuild = GuildsPagesController::canAdminGuild($linkedUser, $guildPage["g_uuid"]);


$form = filter_input(INPUT_POST, 'form');
if($canAdminGuild && isset($form)){
    $formData = array();
    $result = array();
    foreach($_POST AS $key => $value){
        $formData[$key] = filter_input(INPUT_POST, $key);
    }
    
    switch($form){
        case "update-data":
            unset($formData["form"]);
            $formData["g_uuid"] = $guildPage["g_uuid"];
            GuildPagesPersistence::persistGuildPage($formData);
            $guildPage = array_merge($guildPage, $formData);
            break;

        case "refresh-with-api":

            break;

        case "hide":

            break;

        case "delete":

            break;

        case "create-board":

            break;
    }
}