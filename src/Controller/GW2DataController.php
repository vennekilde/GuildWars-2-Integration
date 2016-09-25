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

use GW2Integration\API\GW2APICommunicator;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Exceptions\GW2APIException;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Treasures\GW2Api\GW2Api;

/**
 * Description of GW2DataController
 *
 * @author Jeppe Boysen Vennekilde
 */
class GW2DataController {
    
    /**
     *
     * @var GW2Api 
     */
    public static $gw2API;
    
    public static function init(){
        static::$gw2API = new GW2Api(array("timeout" => 5));
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @param boolean $createNewIfNotExists
     */
    public static function resyncAccountEndpoint($linkedUser, $apiKey, $createNewIfNotExists = false) {
        //Get data from Account endpoint
        $accountData = (array)static::$gw2API->account($apiKey)->get();
        
        //Persist new account data
        GW2DataPersistence::persistAccountData($linkedUser, $accountData, $createNewIfNotExists);

        //Persist potential new guild memberships
        GW2DataPersistence::persistGuildMemberships($linkedUser, $accountData["guilds"]);
        
        return true;
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @return boolean
     */
    function resyncCharactersEndpoint($linkedUser, $apiKey) {
        //Get data from Account endpoint
        $json = (array)static::$gw2API->account($apiKey)->get();

        foreach($json AS $character){
            //\IPS\Session::i()->log(null,  'character : ' . json_encode($character));
            GW2DataPersistence::persistCharacterData($linkedUser, $character);
            GW2DataPersistence::persistCharacterCraftingProfessions($character["name"], $character["crafting"]);
        }
        return true;
    }
    
    
    public static function fetchGuildsData($guildIds, $checkLastSynched = true){
        $guildsAlreadySynched = GW2DataPersistence::getGuildsAlreadySynched($guildIds, $checkLastSynched);
        $isArray = is_array($guildsAlreadySynched);
        foreach($guildIds AS $guildId){
            if(!$isArray || !in_array($guildId, $guildsAlreadySynched)){
                //Old V1 endpoint not supported by gw2treasures/gw2api, so used custom api communicator
                try{
                    $guildDetails = GW2APICommunicator::requestGuildDetails($guildId);
                    $json = $guildDetails->getJsonResponse();
                    GW2DataPersistence::persistGuildDetails($json);
                } catch(GW2APIException $e){
                    global $logger;
                    $logger->error($e->getMessage(), $e->getTrace());
                }
            }
        }
    }
}
GW2DataController::init();