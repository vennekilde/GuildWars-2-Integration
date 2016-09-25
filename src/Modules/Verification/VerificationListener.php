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

namespace GW2Integration\Modules\Verification;

use GW2Integration\Events\EventListener;
use GW2Integration\Events\Events\GW2DataPrePersistEvent;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\StatisticsAndLoggingPersistenceHelper;

/**
 * Description of VerificationListener
 *
 * @author Jeppe Boysen Vennekilde
 */
class VerificationListener implements EventListener{
    public function onGW2DataPrePersistEvent(GW2DataPrePersistEvent $event){
        if($event->getEndpoint() == "v2/account"){
            $linkedUser = $event->getLinkedUser();
            try{
                $oldData = GW2DataPersistence::getAccountData($linkedUser);
            } catch(UnableToDetermineLinkId $ex){
                $oldData = array(
                    "a_world" => null
                );
            }
            $newData = $event->getData();
            
            $oldWorld = $oldData["a_world"];
            $newWorld = $newData[":a_world"];
            if($oldData["a_world"] != $newData[":a_world"]){
                //Since this is handled pre-persist, the user might not have been assigned a link-id yet
                //Instead register a method that will be ran when the user recieves a
                //link-id. Will run instantly if link-id is already assigned
                $listenerMethod = function() use($linkedUser, $oldWorld, $newWorld){
                    global $logger;
                    StatisticsAndLoggingPersistenceHelper::persistVerificationEvent($linkedUser, 0, $oldWorld . "," . $newWorld);
                    
                    $logger->info($linkedUser->compactString()." has changed world from ".(empty($oldWorld) ? "NO PREVIOUSE WORLD" : $oldWorld)." to $newWorld");

                    foreach(ModuleLoader::getVerificationModules() AS $verificationModule){
                        $verificationModule->handleUserWorldChanged($linkedUser, $oldWorld, $newWorld);
                    }
                };
                    
                $linkedUser->addOnLinkedIdSetListener($listenerMethod);
            }
        }
    }
}
