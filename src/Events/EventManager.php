<?php

/*
 * The MIT License
 *
 * Copyright 2015 jeppe.
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

namespace GW2Integration\Events;

use Exception;
use GW2Integration\Events\Events\Event;
use ReflectionClass;

/**
 * Description of EventManager
 *
 * @author jeppe
 */
class EventManager {
    private static $listeners = array();
    
    public static function registerListener(EventListener $listener){
        global $logger;
        $listenerHash = spl_object_hash($listener);
        if(!array_key_exists($listenerHash, EventManager::$listeners)){
            $listenerData = array();
            $listenerData["listener"] = $listener;
            $listenerData["events"] = array();

            $listenerClass = new ReflectionClass($listener);
            foreach ($listenerClass->getMethods() as $classMethod) {
                $params = $classMethod->getParameters();
                if(isset($params[0])){
                    $param = $params[0];
                    if(!($param->getClass() == null || $param->getClass() === false) && $param->getClass()->isSubclassOf("GW2Integration\Events\Events\Event")){
                        $eventClassName = $param->getClass()->getName();
                        if(!isset($listenerData["events"][$eventClassName])){
                            $listenerData["events"][$eventClassName] = array();
                        }
                        $listenerData["events"][$eventClassName][] = $classMethod;
                    }
                }
            }
            if(count($listenerData["events"]) > 0){
                EventManager::$listeners[$listenerHash] = $listenerData;
                $logger->debug("Registering Listener: ".$listenerClass->getName());
                foreach ($listenerData["events"] as $key => $value) {
                    $logger->debug("Listening for: $key");
                }
            }
        }
    }
    
    public static function unregisterListener(EventListener $listener){
        global $logger;
        $logger->debug("Unregistering Listener: ".get_class($listener));
        $listenerHash = spl_object_hash($listener);
        unset(EventManager::$listeners[$listenerHash]);
    }
    
    public static function unregisterAllListeners(){
        global $logger;
        $logger->debug("Unregistering ALL Listeners");
        unset(EventManager::$listeners);
        EventManager::$listeners = array();
    }
    
    /**
     * Fire an event for all registered listeners to recieve
     * @global type $logger
     * @param Event $event
     * @throws Exception
     */
    public static function fireEvent(Event $event){
        global $logger;
        $eventClass = new ReflectionClass($event);
        $logger->debug("Firering Event: $eventClass->name");
        $args = array($event);
        foreach(EventManager::$listeners as $listenerData){
            foreach($listenerData["events"] as $eventName => $listeningMethods){
                if($eventClass->name == $eventName || $eventClass->isSubclassOf($eventName)){
                    foreach($listeningMethods as $listeningMethod){
                        $logger->debug("Listener: ".get_class($listenerData["listener"])." - Method: ".$listeningMethod->getName());
                        try{
                            $listeningMethod->invokeArgs($listenerData["listener"], $args);
                        } catch(Exception $e){
                            if($e instanceof EventManageIgnoreException){
                                throw $e;
                            } else {
                                $logger->error("Exception while firering event: ". get_class($e) . " - " . $e->getMessage(), $e->getTrace());
                            }
                        }
                    }
                }
            }
        }
    }
}
