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

namespace GW2Integration\REST\User;

/**
 * Description of SetAPIKey
 *
 * @author Jeppe Boysen Vennekilde
 */

require __DIR__.'/../RESTHelper.php';

use Exception;
use GW2Integration\API\APIKeyManager;
use GW2Integration\Exceptions\AccountAlreadyLinked;
use GW2Integration\Exceptions\AccountUsernameBanned;
use GW2Integration\Exceptions\InvalidAPIKeyFormatException;
use GW2Integration\Exceptions\InvalidAPIKeyNameException;
use GW2Integration\Exceptions\MissingRequiredAPIKeyPermissions;
use GW2Integration\Exceptions\RequirementsNotMetException;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\REST\RESTHelper;
use GW2Treasures\GW2Api\V2\Authentication\Exception\AuthenticationException;
use function GuzzleHttp\json_encode;

$linkedUser = RESTHelper::getLinkedUserFromParams();

$apiKey = htmlspecialchars(filter_input(INPUT_POST, 'api-key'));
//Attempt to add API Key
try {
    APIKeyManager::addAPIKeyForUser($linkedUser, $apiKey);
    
    //Respond with newly persisted account data
    $accountData = GW2DataPersistence::getExtensiveAccountData($linkedUser);
    echo json_encode($accountData);
    
} catch (Exception $e) {
    global $logger;
    
    $baseErrorMsg = $linkedUser->compactString().' - API Key: '.$apiKey.' - SetAPIKey Exception: ' . get_class($e) . ": ";
    //Exception handling
    if ($e instanceof InvalidAPIKeyNameException || $e instanceof InvalidAPIKeyFormatException) {
        http_response_code(417); //invalid api keyname
        $logger->info($baseErrorMsg . $e->getMessage());
        echo $e->getMessage();
        
    } else if ($e instanceof MissingRequiredAPIKeyPermissions) {
        http_response_code(401); //Unauthorized
        $permissions = $e->getPermissions();
        $logger->info($baseErrorMsg . $e->getMessage(),$permissions);
        echo '["' . implode('","', $permissions) . '"]';
    } else if($e instanceof UnableToDetermineLinkId){
        $logger->info($baseErrorMsg . $e->getMessage(), $e->getTrace());
        echo "false";
    } else {
        http_response_code(406); //Not acceptable
        $errorMsg = $e->getMessage();
        if($e instanceof AuthenticationException){
            //Could not add API key for what ever reason
            $logger->info($baseErrorMsg . $e->getMessage());
            $errorMsg = "API Key is invalid or the API is down";
        } else if(
                $e instanceof AccountAlreadyLinked 
                || $e instanceof AccountUsernameBanned 
                || $e instanceof RequirementsNotMetException){
            $logger->info($baseErrorMsg . $e->getMessage());
        } else {
            //Could not add API key for what ever reason
            $logger->info($baseErrorMsg . $e->getMessage(), $e->getTrace());
        }
        echo $errorMsg;
    }
    exit(0);
}

http_response_code(202); //Accepted
//End response early, no reason to send rest
exit(0);