<?php
namespace GW2Integration\Processes;

use Exception;
use GW2Integration\Events\EventListener;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\LinkedServices\SMF\SimpleMachinesForum;
use GW2Integration\LinkedServices\Teamspeak\Teamspeak;
use GW2Integration\Persistence\Helper\ServicePersistencyHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Treasures\GW2Api\GW2Api;

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

/**
 * Description of Cleanup
 *
 * @author Jeppe Boysen Vennekilde
 */
class RefreshLinkedServers implements EventListener{
    public function __construct() {
        EventManager::registerListener($this);
    }
    
    public function onAPISyncCompleted(APISyncCompleted $event){
        global $logger;
        try{
            $api = new GW2Api();
            $homeWorld = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::HOME_WORLD);
            $overviewJson = (array)$api->wvw()->matches()->overview()->world($homeWorld);
            foreach($overviewJson["worlds"] AS $key => $value){
                if($value == $homeWorld){
                    $worldColor = $key;
                    break;
                }
            }
            
            $linkedWorlds = $overviewJson["all_worlds"]->$worldColor;
            $homeWorldKey = array_search($homeWorld, $linkedWorlds);
            //Sanity check, if home world is not set, then the linked worlds will be wrong
            if(isset($homeWorldKey) && $homeWorldKey >= 0){
                unset($linkedWorlds[$homeWorldKey]);
                
                $persistedLinkedWorlds = explode(",", 
                        SettingsPersistencyHelper::getSetting(
                            SettingsPersistencyHelper::LINKED_WORLDS
                        ));
                
                if(!empty(array_diff($linkedWorlds, $persistedLinkedWorlds))){
                    foreach($persistedLinkedWorlds AS $world){
                        if(!in_array($world, $linkedWorlds)){
                            //Remove old link
                            $this->removeLink($world);
                        }
                    }

                    foreach($linkedWorlds AS $world){
                        if(!in_array($world, $persistedLinkedWorlds)){
                            //Add new link
                            $this->addLink($world);
                        }
                    }

                    $linkedWorldsStr = implode(",", $linkedWorlds);
                    SettingsPersistencyHelper::persistSetting(
                            SettingsPersistencyHelper::LINKED_WORLDS, $linkedWorldsStr);
                    $logger->info("Updated linked worlds to " . $linkedWorldsStr);
                }
            }
        } catch(Exception $e){
            $logger->error($e->getMessage(), $e->getTrace());
        }
    }
    
    public function addLink($world){
        global $logger;
        $smfPGroup = SettingsPersistencyHelper::getSetting(
                SettingsPersistencyHelper::SMF_LINKED_WORLD_TEMP_GROUP_PRIMARY);
        $tsPGroup = SettingsPersistencyHelper::getSetting(
                SettingsPersistencyHelper::TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_PRIMARY);
        $tsSGroup = SettingsPersistencyHelper::getSetting(
                SettingsPersistencyHelper::TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_SECONDARY);
        
        if(!empty($smfPGroup)){
            ServicePersistencyHelper::persistWorldToGroupSettings(
                    $world, SimpleMachinesForum::serviceId, 1, $smfPGroup);
        }
        if(!empty($tsPGroup)){
            ServicePersistencyHelper::persistWorldToGroupSettings(
                    $world, Teamspeak::serviceId, 1, $tsPGroup);
        }
        if(!empty($tsSGroup)){
            ServicePersistencyHelper::persistWorldToGroupSettings(
                    $world, Teamspeak::serviceId, 0, $tsSGroup);
        }
        $logger->info("Added linked world " . $world);
    }
    
    public function removeLink($world){
        global $logger;
        $smfPGroup = SettingsPersistencyHelper::getSetting(
                SettingsPersistencyHelper::SMF_LINKED_WORLD_TEMP_GROUP_PRIMARY);
        $tsPGroup = SettingsPersistencyHelper::getSetting(
                SettingsPersistencyHelper::TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_PRIMARY);
        $tsSGroup = SettingsPersistencyHelper::getSetting(
                SettingsPersistencyHelper::TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_SECONDARY);
        
        if(!empty($smfPGroup)){
            ServicePersistencyHelper::removeWorldToGroupSettings(
                    $world, SimpleMachinesForum::serviceId, 1, $smfPGroup);
        }
        if(!empty($tsPGroup)){
            ServicePersistencyHelper::removeWorldToGroupSettings(
                    $world, Teamspeak::serviceId, 1, $tsPGroup);
        }
        if(!empty($tsSGroup)){
            ServicePersistencyHelper::removeWorldToGroupSettings(
                    $world, Teamspeak::serviceId, 0, $tsSGroup);
        }
        $logger->info("Removed linked world " . $world);
    }
}
