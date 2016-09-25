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
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * 
 */
class LinkedUserController {

    const TEMPORARY_API_KEY_NAME = "temporary";
    const TEMPORARY_API_KEY_PERMISSIONS = "none";
    /**
     * 
     * @param LinkedUser $linkedUser
     */
    public static function getServiceLinks($linkedUser){
        $serviceLinks = LinkingPersistencyHelper::getServiceLinks($linkedUser);

        //Clear current service ids
        $linkedUser->primaryServiceIds = array();
        $linkedUser->secondaryServiceIds = array();
        foreach($serviceLinks AS $serviceLink){
            $linkedUser->setLinkedId($serviceLink["link_id"]);
            if($serviceLink["is_primary"] == 1){
                $linkedUser->setPrimaryarySeviceId($serviceLink["service_user_id"], $serviceLink["service_id"], $serviceLink["service_display_name"]);
            } else {
                $linkedUser->addSecondarySeviceId($serviceLink["service_user_id"], $serviceLink["service_id"], $serviceLink["service_display_name"]);
            }
        }
        return $linkedUser;
    }

    /**
     * 
     * @param LinkedUser $linkedUser
     * @param type $serviceUserId
     * @param type $serviceId
     */
    public static function deleteServiceLink($linkedUser, $serviceUserId, $serviceId){
        return LinkingPersistencyHelper::deleteServiceLink($linkedUser, $serviceUserId, $serviceId);
    }
}
