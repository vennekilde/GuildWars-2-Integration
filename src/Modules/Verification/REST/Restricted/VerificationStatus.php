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

require __DIR__.'/../../../../REST/RestrictedRESTHelper.php';

use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Modules\Verification\VerificationController;
use GW2Integration\Modules\Verification\VerificationStatus;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\REST\RestrictedRESTHelper;
use function GuzzleHttp\json_encode;

$response = array();

$linkedUsers = RestrictedRESTHelper::getLinkedUsersFromParamsUnsafe();
$useEnumsIds = isset($_REQUEST["enum-ids"]);
foreach($linkedUsers AS $id => $linkedUser){
    try{
        $response[$id] = processAccountData($linkedUser, GW2DataPersistence::getExtensiveAccountData($linkedUser), $useEnumsIds);
        LinkingPersistencyHelper::updateDisplayName($linkedUser->fetchServiceUserId, $linkedUser->fetchServiceId, $linkedUser->fetchServiceDisplayName);
    } catch(UnableToDetermineLinkId $ex){
        if($useEnumsIds){
            $accessStatus = VerificationStatus::ACCESS_DENIED_ACCOUNT_NOT_LINKED;
        } else {
            $accessStatus = "ACCESS_DENIED_ACCOUNT_NOT_LINKED";
        }
        $response[$id] = array(
            "status" => $accessStatus
        );
    }
}

function processAccountData($linkedUser, $accountData, $useEnumsIds = false){
    $verificationStatus = VerificationController::getVerificationStatusFromAccountData($linkedUser, $accountData, true);
    $result = $verificationStatus->toJSON($useEnumsIds);
    return $result;
    
}
        
echo json_encode($response);