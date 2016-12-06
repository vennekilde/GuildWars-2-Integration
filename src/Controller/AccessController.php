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

use GW2Integration\LinkedServices\LinkedService;

/**
 * Description of AccessController
 *
 * @author Jeppe Boysen Vennekilde
 */
class AccessController {

    public static function isAdmin() {
        $canViewAdminPanel = false;
        global $gw2i_linkedServices, $gw2i_admin_allowed;
        foreach ($gw2i_admin_allowed AS $serviceId => $groups) {
            $service = $gw2i_linkedServices[$serviceId];
            /* @var $service LinkedService */
            if (isset($service) && $service->canCheckGroupId()) {
                $userUserviceLink = $service->getAvailableUserServiceLink();

                if (isset($userUserviceLink)) {
                    $userId = $userUserviceLink->getServiceUserId();
                    foreach ($groups AS $group) {
                        $hasGroup = $service->hasUserGroupId($userId, $group);
                        if ($hasGroup) {
                            $canViewAdminPanel = true;
                            goto doneAdminAccessCheck;
                        }
                    }
                }
            }
        }
        doneAdminAccessCheck:
        return $canViewAdminPanel;
    }

}
