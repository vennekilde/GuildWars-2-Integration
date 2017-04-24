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
use GW2Integration\Modules\Guilds\ModuleLoader;
use GW2Integration\Persistence\GW2Schema;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\Utils\GW2DataFieldConverter;
use GW2Treasures\GW2Api\GW2Api;
use Illuminate\Database\Capsule\Manager as Capsule;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

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
        
        //Process individual guild memberships
        //Temporary, using V2 code
        if(isset($linkedUser->getPrimaryUserServiceLinks()[0]) && $linkedUser->getPrimaryUserServiceLinks()[0]->getServiceUserId() != null){
            ModuleLoader::updateGuildAssociation($linkedUser->getPrimaryUserServiceLinks()[0]->getServiceUserId(), $accountData["guilds"], $accountData["world"]);
        }
        
        return true;
    }
    
    /**
     * 
     * @param LinkedUser $linkedUser
     * @param string $apiKey
     * @return boolean
     */
    public static function updateCharacters($linkedUser, $apiKey) {
        //Get data from Account endpoint
        //Quick and dirty stdObject to array conversion
        $json = json_decode(json_encode(static::$gw2API->characters($apiKey)->all()), true);

        $localCharacters = Capsule::table(GW2Schema::$characters->getTableName())->
                where("link_id", LinkingPersistencyHelper::determineLinkedUserId($linkedUser))->get()->all();
        
        
        //Infered & collected data combined into single data points
        $accountDataExtended = array();
        
        foreach($json AS $character){
            $characterId = null;
            $character["link_id"] = $linkedUser->getLinkedId();
            $character["race"] = GW2DataFieldConverter::getRaceIdFromString($character["race"]);
            $character["gender"] = GW2DataFieldConverter::getGenderIdFromString($character["gender"]);
            $character["profession"] = GW2DataFieldConverter::getProfessionIdFromString($character["profession"]);
            //Remove TZ to compare with stored datetimes
            $character["created"] = str_replace("T", " ", $character["created"]);
            $character["created"] = str_replace("Z", "", $character["created"]);
            
            foreach($localCharacters AS $key => $localCharacter){
                if(     $localCharacter->created == $character["created"]
                     && $localCharacter->race == $character["race"]
                     && $localCharacter->profession == $character["profession"]
                     && $localCharacter->level <= $character["level"]
                     && $localCharacter->age <= $character["age"]
                     && $localCharacter->deaths <= $character["deaths"]){
                    
                    $characterId = $localCharacter->id;
                    //Don't need the character data in the array anymore, 
                    //as it will just slow down the comparison
                    unset($localCharacters[$key]);
                    break;
                }
            }
            $characterData = GW2Schema::getSupportedColumns($character, GW2Schema::$characters);
            if(isset($characterId)){
                Capsule::table(GW2Schema::$characters->getTableName())->where("id",$characterId)->update($characterData);
            } else {
                Capsule::table(GW2Schema::$characters->getTableName())->insert($characterData);
                $characterId = Capsule::connection()->getPdo()->lastInsertId();
            }
            if(empty($accountDataExtended)){
                $accountDataExtended["deaths"] = $character["deaths"];
                $accountDataExtended["playtime"] = $character["age"];
            } else {
                $accountDataExtended["deaths"] += $character["deaths"];
                $accountDataExtended["playtime"] += $character["age"];
            }
            
            //Persist each crafting profession
            self::updateCharacterCrafting($characterId, $character["crafting"]);
        }
        
        if(!empty($accountDataExtended)){
            
            //Update or insert
            $table = Capsule::table(GW2Schema::$accountDataExtended->getTableName());
            $attributes = array("link_id" => $linkedUser->getLinkedId());
            if (! $table->where($attributes)->exists()) {
                $table->insert(array_merge($attributes, $accountDataExtended));
            } else {
                $table->update($accountDataExtended);
            }
        }
            
        //If a character is still in the local array, it means the user is no
        //longer owned by the player
        foreach($localCharacters AS $localCharacter){
            self::deleteCharacter($localCharacter->id);
        }
        return true;
    }
    
    /**
     * 
     * @param type $characterId
     * @param type $craftingDisciplines
     * @return boolean
     */
    public static function updateCharacterCrafting($characterId, $craftingDisciplines) {
        //Persist each crafting profession
        foreach($craftingDisciplines AS $crafting){
            $crafting = GW2Schema::getSupportedColumns(
                    $crafting, GW2Schema::$characters_crafting);

            $discipline = GW2DataFieldConverter::getCraftingDisciplineIdFromString($crafting["discipline"]);
            unset($crafting["discipline"]);
            
            //Update or insert
            $table = Capsule::table(GW2Schema::$characters_crafting->getTableName());
            $attributes = array("id" => $characterId, "discipline" => $discipline);
            if (! $table->where($attributes)->exists()) {
                $table->insert(array_merge($attributes, $crafting));
            } else {
                $table->update($crafting);
            }
        }
            
        return true;
    }
    
    public static function deleteCharacter($characterId){
        Capsule::table(GW2Schema::$characters->getTableName())->where("id", $characterId)->delete();
        Capsule::table(GW2Schema::$characters_crafting->getTableName())->where("id", $characterId)->delete();
    }
    
    
    public static function fetchGuildsData($guildIds, $checkLastSynched = true){
        $guildsAlreadySynched = GW2DataPersistence::getGuildsAlreadySynched($guildIds, $checkLastSynched);
        if(!is_array($guildsAlreadySynched)){
            return;
        }
        foreach($guildIds AS $guildId){
            $key = array_search($guildId, $guildsAlreadySynched);
            if($key === false){
                //Old V1 endpoint not supported by gw2treasures/gw2api, so used custom api communicator
                try{
                    $guildDetails = GW2APICommunicator::requestGuildDetails($guildId);
                    $json = $guildDetails->getJsonResponse();
                    GW2DataPersistence::persistGuildDetails($json);
                } catch(GW2APIException $e){
                    global $logger;
                    $logger->error("Exception ".get_class($e) . ": " . $e->getMessage());
                }
            } else {
                //Optimization, no need to check if the guildid is in the array anymore
                unset($guildsAlreadySynched[$key]);
            }
        }
    }
}
GW2DataController::init();