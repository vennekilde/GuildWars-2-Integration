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

namespace GW2Integration\API;

use Exception;
use GW2Integration\Controller\VerificationController;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Events\EventManager;
use GW2Integration\Events\Events\APISyncCompleted;
use GW2Integration\Persistence\Helper\APIKeyPersistenceHelper;


if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * Description of APIBatchProcessor
 *
 * @author Jeppe Boysen Vennekilde
 */
class APIMultiThreadedProcessor {
    
    public function process(){
        global $gw2i_proccess_keys_per_run, $logger;
        $apiKeysQuery = APIKeyPersistenceHelper::queryAPIKeys(0, $gw2i_proccess_keys_per_run);
        foreach($apiKeysQuery AS $apiKeyData){
            print_r($apiKeyData);
            $linkedUser = new LinkedUser();
            $linkedUser->setLinkedId($apiKeyData["link_id"]);
            try {
                VerificationController::getServiceLinks($linkedUser);
                APIKeyProcessor::resyncAPIKey(
                        $linkedUser, 
                        $apiKeyData["api_key"], 
                        explode(",", $apiKeyData["api_key_permissions"]),
                        false);
            } catch(Exception $e){
                $logger->error($e->getMessage(), $e->getTrace());
            }
        }
        EventManager::fireEvent(new APISyncCompleted());
    }
}

class APIMultiThreadedPool {

}
