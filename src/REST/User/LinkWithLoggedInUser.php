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

namespace GW2Integration\REST\User;

require __DIR__.'/../RESTHelper.php';

use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\REST\RESTHelper;
use function GuzzleHttp\json_encode;

global $gw2i_linkedServices;

$serviceId = filter_input(INPUT_POST, 'service-id');
$isPrimary = filter_input(INPUT_POST, 'is-primary');

$response = array();
if(isset($serviceId) && isset($gw2i_linkedServices[$serviceId])){
    //default isPrimary to true if not present
    $isPrimary = isset($isPrimary) ? $isPrimary : true;
    //Get the logged in user
    $userUserviceLink = $gw2i_linkedServices[$serviceId]->getAvailableUserServiceLink();
    if(isset($userUserviceLink)){
        $linkedUser = RESTHelper::getLinkedUserFromParams();
        
        $response["result"] = $userUserviceLink;
        
        try{
            $userServiceLink = new UserServiceLink(
                        $serviceId, 
                        $response["result"][0], 
                        $isPrimary,
                        $response["result"][1]);
            $userServiceLink->setLinkedId($linkedUser->getLinkedId());
            LinkingPersistencyHelper::persistUserServiceLink($userServiceLink);
        } catch(UnableToDetermineLinkId $e){
            $response["result"] = false;
            $response["msg"] = "UnableToDetermineLinkId";
        }
    } else {
        $response["result"] = false;
        $response["msg"] = "Not logged in on service";
    }
} else {
    $response["result"] = false;
    $response["msg"] = "Missing/invalid params";
}

echo json_encode($response);