<?php

namespace GW2Integration\REST;

use GW2Integration\Controller\ServiceSessionController;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;

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

require_once __DIR__ . "/../Source.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class RESTHelper{
    /**
     * Get linked users from the request params
     * These users cannot be used for verifying the identity of the user calling
     * the function
     * @return LinkedUser
     */
    public static function getLinkedUsersFromParamsUnsafe(){
        $linkedUsers = array();
        if(isset($_REQUEST["user-ids"]) && isset($_REQUEST["service-id"])){
            $userIds = explode(",", $_REQUEST["user-ids"]);
            foreach ($userIds AS $userInfo){
                $userInfoArray = explode(':', $userInfo, 2);
                $linkedUser = new LinkedUser();
                $linkedUser->fetchServiceUserId = $userInfoArray[0];
                $linkedUser->fetchServiceId = $_REQUEST["service-id"];
                if(isset($userInfoArray[1])){
                    $linkedUser->fetchServiceDisplayName = $userInfoArray[1];
                }
                $linkedUsers[$userInfoArray[0]] = $linkedUser;
            }
        }
        return $linkedUsers;
    }
    
    
    public static function getLinkedUserFromParams(){
        global $session_expiration_periode, $gw2i_linkedServices;
        $linkedUser = new LinkedUser();
        
        //First, get users available directly without looking at the provided session
        foreach($gw2i_linkedServices as $linkedService){
            $userUserviceLink = $linkedService->getAvailableUserServiceLink();
            if(isset($userUserviceLink)){
                $linkedUser->addUserServiceLink($userUserviceLink);
            }
        }
        //Attempt to get linked user data from sessions
        $storeCookie = true;
        $sessionHashes = filter_input(INPUT_POST, 'ls-sessions');
        if(!isset($sessionHashes)){
            //Try get params
            $sessionHashes = filter_input(INPUT_GET, 'ls-sessions');
        }
        if(!isset($sessionHashes)){
            //Try cookies 
            $storeCookie = false;
            //No sessions in POST param, so check if there is any cookie sessions stored
            $sessionHashes = filter_input(INPUT_COOKIE, 'ls-sessions');
        }
        
        //Retrieve the primary id's
        if(isset($sessionHashes) && !empty($sessionHashes)){
            $sessions = explode(",",$sessionHashes);
            $oldestTimestamp = null;
            foreach($sessions AS $session){
                $sessionData = ServiceSessionController::getSessionIfStillValid($session);
                
                //Only use session if link hasn't already been determined
                if(
                        isset($sessionData) 
                        && !empty($sessionData) 
                        //&& !isset($linkedUser->primaryServiceIds[$sessionData["service_id"]])
                ){
                    $linkedUser->addUserServiceLink(
                        new UserServiceLink(
                            $sessionData["service_id"], 
                            $sessionData["service_user_id"], 
                            $sessionData["is_primary"] == "1",
                            $sessionData["service_display_name"]));
                    
                    $timestamp = strtotime($sessionData["timestamp"]);
                    if(!isset($oldestTimestamp) || $timestamp < $oldestTimestamp){
                        $oldestTimestamp = $timestamp;
                    }
                }
            }

            //Store sessions as cookie param
            if($storeCookie){
                global $session_expiration_periode;
                $cookieExpiresIn = $session_expiration_periode - (time() - $oldestTimestamp);
                setcookie("ls-sessions", $sessionHashes, $cookieExpiresIn);  /* expire in 1 hour */
            }
        }
        
        $primaryLinks = $linkedUser->getPrimaryUserServiceLinks();
        if(empty($primaryLinks)){
            return null;
        }
        return $linkedUser;
    }
    
    /**
     * 
     * @global type $gw2i_linkedServices
     * @return UserUserviceLink
     * @throws MainServiceConflictException
     */
    public static function getMainUserServiceLink(){
        global $gw2i_linkedServices;
        $mainUserUserviceLink = null;
        foreach($gw2i_linkedServices as $linkedService){
            $userUserviceLink = $linkedService->getAvailableUserServiceLink();
            if(isset($userUserviceLink)){
                if(isset($mainUserUserviceLink)){
                    throw new MainServiceConflictException();
                } else {
                    $mainUserUserviceLink = $userUserviceLink;
                }
            }
        }
        return $mainUserUserviceLink;
    }
    
    /**
     * 
     * @return UserUserviceLink[]
     */
    public static function getSessionUserServiceLinks(){
        $userServiceLinks = array();
        
        //Attempt to get linked user data from sessions
        $storeCookie = true;
        $sessionHashes = filter_input(INPUT_POST, 'ls-sessions');
        if(!isset($sessionHashes)){
            //Try get params
            $sessionHashes = filter_input(INPUT_GET, 'ls-sessions');
        }
        if(!isset($sessionHashes)){
            //Try cookies 
            $storeCookie = false;
            //No sessions in POST param, so check if there is any cookie sessions stored
            $sessionHashes = filter_input(INPUT_COOKIE, 'ls-sessions');
        }
        
        //Retrieve the primary id's
        if(isset($sessionHashes) && !empty($sessionHashes)){
            $sessions = explode(",",$sessionHashes);
            $oldestTimestamp = null;
            foreach($sessions AS $session){
                $sessionData = ServiceSessionController::getSessionIfStillValid($session);
                
                //Only use session if link hasn't already been determined
                if(
                        isset($sessionData) 
                        && !empty($sessionData) 
                        //&& !isset($linkedUser->primaryServiceIds[$sessionData["service_id"]])
                ){
                    $userServiceLinks[] = 
                        new UserServiceLink(
                            $sessionData["service_id"], 
                            $sessionData["service_user_id"], 
                            $sessionData["is_primary"] == "1",
                            $sessionData["service_display_name"]);
                    
                    $timestamp = strtotime($sessionData["timestamp"]);
                    if(!isset($oldestTimestamp) || $timestamp < $oldestTimestamp){
                        $oldestTimestamp = $timestamp;
                    }
                }
            }

            //Store sessions as cookie param
            if($storeCookie){
                global $session_expiration_periode;
                $cookieExpiresIn = $session_expiration_periode - (time() - $oldestTimestamp);
                setcookie("ls-sessions", $sessionHashes, $cookieExpiresIn);  /* expire in 1 hour */
            }
        }
        return $userServiceLinks;
    }
}