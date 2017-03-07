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

namespace GW2Integration\API;

use GW2Integration\Events\Events\GW2ResponseEvent;
use GW2Integration\Exceptions\GW2APIKeyException;
use GW2Integration\Exceptions\MalformedGW2ResponseException;
use function GuzzleHttp\json_decode;
/**
 * Description of GW2APICommunicator
 *
 * @author jeppe
 */

class GW2APICommunicator {
    const baseURL = "https://api.guildwars2.com/";
    
    /**
     * 
     * @param type $endPoint
     * @param type $apiKey
     * @param type $retries
     * @param type $timeout
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function makeRequestWithRetry($endPoint, $apiKey, $retries = 3, $timeout = 5){
        for($i = 0; $i < $retries; $i++){
            try{
                $gw2ResponseEvent = static::makeRequest($endPoint, $apiKey, $timeout);
                break;
            } catch(GW2APIKeyException $e){
                if($i + 1 < $retries){
                    if($e->getHttpCode() == 503){
                        continue;
                    }
                }
                throw $e;
            }
        }
        return $gw2ResponseEvent;
    }
    
    /**
     * Make a request for the GW2 JSON API
     * @param string $endPoint
     * @param string $apiKey
     * @return GW2ResponseEvent
     */
    public static function makeRequest($endPoint, $apiKey, $timeout = 5) {
        try{
            //Prepare cURL request
            $curl = curl_init();
            //Set URL
            curl_setopt($curl, CURLOPT_URL, static::baseURL . $endPoint);
            //Return response as a String instead of outputting to a screen
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,0); 
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            //Prepare Request Header
            $headers = array();
            $headers[] = 'Authorization: Bearer ' . $apiKey;
            //Add Request Header
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            //Perform request
            $response = curl_exec($curl);
            //\IPS\Session::i()->log(null,  'GW2 API Response: ' . $response );
            //HTTP Status
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            //Check if request was successful
            if($http_status == 200){
                //Decode Json response
                $json = json_decode($response, true);

                //Check if request was successful
                if (isset($json["text"]) && $json["text"] == "endpoint requires authentication") {
                    throw new GW2APIKeyException('endpoint requires authentication', $apiKey, $http_status, $response, 1);

                //Known to be set if endpoint can't be found
                } elseif(isset($json["error"])){
                    throw new GW2APIKeyException($json["error"], $apiKey, $response, $http_status, 2);
                }
            } else if($response === false){
                throw new GW2APIKeyException('CURL Error: ' . curl_error($curl) . '. for endpoint '.$endPoint.' HTTP Code: '.$http_status, $apiKey, $response, $http_status, -1);
            } else {
                throw new GW2APIKeyException('HTTP Code: '.$http_status, $apiKey, $response, $http_status, -1);
            }

            $gw2ResponseEvent = new GW2ResponseEvent(-1, $json, $response, $endPoint, $http_status);
            return $gw2ResponseEvent;
        } finally {
            //Close request connection
            curl_close($curl);
        }
    }
    
    /**
     * Request information from the account endpoint
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestAccountInfo($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/account", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        if (
                !isset($json["id"]) ||
                !isset($json["name"]) ||
                !isset($json["world"]) ||
                !isset($json["guilds"])
        ) {
            
            throw new MalformedGW2ResponseException($gw2ResponseEvent, $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
        }
        return $gw2ResponseEvent;
    }
    
    /**
     * Request information from the tokeninfo endpoint
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestAPIKeyInfo($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/tokeninfo", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        if (
                !isset($json["id"]) ||
                //!isset($json["name"]) || //Apparently if an API key is not named anything, name wont be included in the json response
                !isset($json["permissions"])
        ) {
            throw new MalformedGW2ResponseException('Could not parse api key information', $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
        }
        if(!isset($json["name"])){
            $json["name"] = "";
            $gw2ResponseEvent->setJsonResponse($json);
        }
        return $gw2ResponseEvent;
    }
    
    
    /**
     * Request names of characters from the characters endpoint
     * @param type $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestCharactersNames($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/characters", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        if (empty($json)){
            throw new GW2APIKeyException("Zero characters found", $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), -1);
        }
        return $gw2ResponseEvent;
    }
    
    
    /**
     * Request all data of each of the users characters from the characters endpoint
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestCharactersData($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/characters?page=0", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        
        if (empty($json)){
            throw new GW2APIKeyException("Zero characters found", $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), -1);
        }
        
        foreach($json as $character){
            if (
                    !isset($character["name"]) ||
                    !isset($character["race"]) ||
                    !isset($character["gender"]) ||
                    !isset($character["profession"]) ||
                    !isset($character["level"]) ||
                    !isset($character["age"]) ||
                    !isset($character["created"])
            ) {
                throw new MalformedGW2ResponseException($gw2ResponseEvent, $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
            }
        }
        return $gw2ResponseEvent;
    }
    
    /**
     * 
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestPVPStats($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/pvp/stats", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        
        if (empty($json)){
            throw new GW2APIKeyException("PVP stats found", $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), -1);
        }
        
        if (
                !isset($json["pvp_rank"]) ||
                !isset($json["pvp_rank_points"]) ||
                !isset($json["pvp_rank_rollovers"]) ||
                !isset($json["aggregate"]) ||
                !isset($json["ladders"])
        ) {
            throw new MalformedGW2ResponseException($gw2ResponseEvent, $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
        }
        return $gw2ResponseEvent;
    }
    
    /**
     * 
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestPVPGameUUIDs($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/pvp/games", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        
        if (empty($json)){
            throw new GW2APIKeyException("No PVP games found", $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), -1);
        }
        
        if (
                !is_array($json)
        ) {
            throw new MalformedGW2ResponseException($gw2ResponseEvent, $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
        }
        return $gw2ResponseEvent;
    }
    
    /**
     * 
     * @param array $gameUUID
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestPVPGameByUUIDs($gameUUIDs, $apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/pvp/games?ids=".implode(",",$gameUUIDs), $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        
        if (empty($json)){
            throw new GW2APIKeyException("No PVP games found", $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), -1);
        }
        
        if (
                !is_array($json)
        ) {
            throw new MalformedGW2ResponseException($gw2ResponseEvent, $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
        }
        return $gw2ResponseEvent;
    }
    
    /**
     * 
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestPVPSeasonStandings($apiKey, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v2/pvp/standings", $apiKey, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        
        if (empty($json)){
            throw new GW2APIKeyException("No PVP season standings found", $apiKey, $json, $gw2ResponseEvent->getHttpResponseCode(), -1);
        }
        
        return $gw2ResponseEvent;
    }
    
    /**
     * 
     * @param string $apiKey
     * @return GW2ResponseEvent
     * @throws GW2APIKeyException
     */
    public static function requestGuildDetails($guildId, $retries = 3) {
        $gw2ResponseEvent = self::makeRequestWithRetry("v1/guild_details.json?guild_id=$guildId", null, $retries);
        $json = $gw2ResponseEvent->getJsonResponse();
        
        if (
                !isset($json["guild_id"]) ||
                !isset($json["guild_name"]) ||
                !isset($json["tag"])
        ) {
            
            throw new MalformedGW2ResponseException($gw2ResponseEvent, null, $json, $gw2ResponseEvent->getHttpResponseCode(), 3);
        }
        
        return $gw2ResponseEvent;
    }
    
    
}