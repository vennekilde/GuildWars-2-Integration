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

namespace GW2Integration\LinkedServices\Teamspeak;

use Exception;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\LinkedServices\LinkedService;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;

/**
 * Description of Teamspeak
 *
 * @author Jeppe Boysen Vennekilde
 */
class Teamspeak extends LinkedService{
    const serviceId = 1;
    
    function __construct() {
        parent::__construct(
                static::serviceId, 
                "Teamspeak", 
                "Link your GuildWars 2 account with the Teamspeak server", 
                "Connect to the Teamspeak server to recieve a unique link that will make this option available",
                false,
                false,
                true
            );
        
        SettingsPersistencyHelper::$visibleSettings["Teamspeak Settings"] = array(
            SettingsPersistencyHelper::TEAMSPEAK_BOT_ADDRESS,
            SettingsPersistencyHelper::TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_PRIMARY,
            SettingsPersistencyHelper::TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_SECONDARY
        );
    }
    
    /**
     * 
     * @return UserServiceLink|null
     */
    public function getAvailableUserServiceLink() {
        //Only way to determine TS línk is via linked session
        return null;
    }

    /**
     * @param type $successJSFunction This JS function will be called when the has been succesfully set up the link
     * @return string
     */
    public function getLinkSetupHTML($successJSFunction) {
        return null;
    }
    
    public function hasUserGroupId($serviceUserId, $groupId) {
        return false;
    }
    
    public function getConfigPageHTML(){
        return '<h5>Soft Restart Teamspeak Bot</h5>
                <p>Soft restart the teamspeak bot, this means the actual Java VM is not restarted, only the internal components</p>
                <form action="../LinkedServices/Teamspeak/ManagementController.php" method="POST" name="soft-restart-ts" class="default-admin-form">
                    <button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect button-spinner">
                        Soft Restart
                    </button> 
                    <div class="mdl-spinner mdl-js-spinner is-active spinner-button"></div>
                    <div class="response-div"></div>
                    <br /><br />
                </form>';
    }
    
    
    
    /**
     * Send a command to the teamspeak bot via the REST interface
     * @param array $params
     */
    public static function sendRESTCommand($params){
        $teamspeakAddress = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::TEAMSPEAK_BOT_ADDRESS);
        
        $paramsString = "";
        foreach($params AS $key => $value){
            if(!empty($paramsString)){
                $paramsString .= ",";
            }
            if(isset($value)){
                $paramsString .= "$key=$value";
            } else {
                $paramsString .= "$key";
            }
        }
        
        
        $teamspeakLinkServerAddress = "$teamspeakAddress/teamspeak?$paramsString";
        
        try{
            // create a new cURL resource
            $ch = curl_init();

            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            curl_setopt($ch, CURLOPT_URL, $teamspeakLinkServerAddress);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // grab URL and pass it to the browser
            $result = curl_exec($ch);

            if($result === false){
                throw new Exception("Could not connect to teamspeak server. Error: ".curl_error($ch));
            }
        } finally {
            // close cURL resource, and free up system resources
            curl_close($ch);
        }
    }
}