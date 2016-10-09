<?php

require __DIR__.'/../../../../REST/RestrictedRESTHelper.php';

use GW2Integration\Entity\LinkedUser;
use GW2Integration\Modules\Verification\VerificationController;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use function GuzzleHttp\json_encode;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//example
//http://farshiverpeaks.com/fsp/authenticationV3/src/REST/User/GrantTemporaryAccess.php?tsdbid=35694&accesstype=HOME_SERVER

$response = array();
if(isset($_REQUEST["world"])){
    $world = $_REQUEST["world"];
} else if(isset($_REQUEST["accesstype"])) {
    if($_REQUEST["accesstype"] === "HOME_SERVER"){
        $world = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::HOME_WORLD);
    } else if($_REQUEST["accesstype"] === "LINKED_SERVER"){
        $linked_worlds = explode(",", SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::LINKED_WORLDS));
        if(!empty($linked_worlds)){
            $world = $linked_worlds[0];
        }
    }
}
if(isset($_REQUEST["user-id"]) && isset($_REQUEST["service-id"]) && (isset($world))){
    $linkedUser = new LinkedUser();
    $linkedUser->fetchServiceUserId = $_REQUEST["user-id"];
    $linkedUser->fetchServiceId = $_REQUEST["service-id"];
    $linkedUser->fetchServiceDisplayName = isset($_REQUEST["displayname"]) ? $_REQUEST["displayname"] : null;
    
    $sessionHash = VerificationController::grantTemporaryAccess($linkedUser, $world);
    $response = array("result" => "granted");
} else {
    $response = array("error" => "Missing world or user id");
}

echo json_encode($response);