<?php

use GW2Integration\API\APIBatchProcessor;
use GW2Integration\API\APIKeyManager;
use GW2Integration\API\APIKeyProcessor;
use GW2Integration\Controller\ServiceSessionController;
use GW2Integration\Entity\LinkedUser;
use GW2Integration\Entity\UserServiceLink;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\Modules\Verification\VerificationController;
use GW2Integration\Persistence\GW2Schema;
use GW2Integration\Persistence\Helper\GW2DataPersistence;
use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\Persistence\Helper\StatisticsPersistence;
use GW2Integration\Persistence\Helper\VerificationEventPersistence;
use GW2Integration\Utils\GW2DataFieldConverter;
use Illuminate\Database\Capsule\Manager;
use function GuzzleHttp\json_encode;

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

require_once __DIR__ . "/RestrictAdminPanel.php";

$form = filter_input(INPUT_POST, 'form');
$formData = array();
$result = array();
foreach($_POST AS $key => $value){
    $formData[$key] = filter_input(INPUT_POST, $key);
}
switch($form){
    case "search":
        global $statistics;
        if(!empty($formData["search-user-id"]) && (!empty($formData["search-service"]) || (isset($formData["search-service"]) && $formData["search-service"] == "0"))){
            $linkedUser = new LinkedUser();
            $userId = $formData["search-user-id"];
            $serviceId = $formData["search-service"];

            if($serviceId == "link-id"){
                $linkedUser->setLinkedId($userId);
            } else if($serviceId == "account-name"){
                $linkedUser->setLinkedId(LinkingPersistencyHelper::getLinkIdFromAccountName($userId));
            } else {
                $linkedUser->addUserServiceLink(new UserServiceLink($serviceId, $userId, true));
            }
            
            try{
                $extensiveAccountData = GW2DataPersistence::getExtensiveAccountData($linkedUser);
                $result["verification-status"] = VerificationController::getVerificationStatusFromAccountData($linkedUser, $extensiveAccountData)->toJSON(false);
                $result["links"] = LinkingPersistencyHelper::getServiceLinks($linkedUser);
                $result["extensive-account"] = $extensiveAccountData;
                $result["guilds"] = GW2DataPersistence::getGuildMembershipWithGuildsData($linkedUser);
                $result["characters"] = Manager::table(GW2Schema::$characters->getTableName())->where("link_id", $linkedUser->getLinkedId())->get()->all();
                
            } catch (UnableToDetermineLinkId $ex) {
                $result = false;
            }
        } else {
            $result = false;
        }
        break;
        
        
    case "batch-process":
        if(!empty($formData["batch-process-count"])){
            $keysToProcess = intval($formData["batch-process-count"]);
            
            $processor = new APIBatchProcessor();
            $proccessedUsers = $processor->process($keysToProcess);
            $stringResult = "";
            foreach($proccessedUsers AS $user){
                $stringResult .= $user->compactString() . "\n";
            }
            $result = array( 
                "proccessed-users" => $stringResult
            );
        }
        break;
        
    
    case "sync-users":
        if(isset($formData["sync-service"]) && isset($formData["sync-users"])){
            $userIds = explode(",", $formData["sync-users"]);
            $serviceId = $formData["sync-service"];
            
            foreach($userIds AS $userId){
                try{ 
                    $linkedUser = new LinkedUser();
                    if($serviceId == "link-id"){
                        $linkedUser->setLinkedId($userId);
                    } else {
                        $linkedUser->addUserServiceLink(new UserServiceLink($serviceId, $userId, true));
                    }
                    APIKeyProcessor::resyncLinkedUser($linkedUser);
                    $result[$userId] = "Success";
                } catch (Exception $e){
                    $result[$userId] = "Exception: ".$e->getMessage();
                }
            }
        } else {
            $result["error"] = "Missing Input";
        }
        break;
    
    
    case "gen-user-session":
        if(isset($formData["gen-session-user-id"]) && isset($formData["gen-session-user-ip"]) && isset($formData["gen-service"])) {
            
            $baseURL = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::SETUP_WEB_PATH);
            
            $sessionHash = ServiceSessionController::createServiceSession(
                    $formData["gen-session-user-id"], 
                    $formData["gen-service"], 
                    $formData["gen-session-user-ip"], 
                    null, 
                    isset($formData["is-primary"]));
            
            $url = $baseURL . (parse_url($baseURL, PHP_URL_QUERY) ? '&' : '?') . 'ls-sessions='.$sessionHash;
            $result["session-url"] = $url;
        } else {
            $result["error"] = "Missing Input";
        }
        break;
        
        
    case "set-api-key":
        if(isset($formData["user-id"]) && !empty($formData["api-key"]) && isset($formData["set-key-service"])) {
            $linkedUser = new LinkedUser();
            $userId = $formData["user-id"];
            $apiKey = $formData["api-key"];
            $serviceId = $formData["set-key-service"];
            $linkedUser->addUserServiceLink(new UserServiceLink($serviceId, $userId, true));
            
            try {                
                $keyNames = APIKeyManager::addAPIKeyForUser($linkedUser, $apiKey);
                $result["result"] = "success";
            } catch(Exception $e){
                $result["error"] = "Exception: ".$e->getMessage()."\n".$e->getTraceAsString();
            }
        } else {
            $result["error"] = "Missing Input";
        }
        break;
        
        
    case "get-api-key-name":
        if(isset($formData["user-id"]) && isset($formData["get-key-name-service"])) {
            $linkedUser = new LinkedUser();
            $userId = $formData["user-id"];
            $serviceId = $formData["get-key-name-service"];
            $linkedUser->addUserServiceLink(new UserServiceLink($serviceId, $userId, true));
            
            $keyNames = APIKeyManager::getAPIKeyNamesForUser($linkedUser);
            $result["valid-api-key-names"] = $keyNames;
        } else {
            $result["error"] = "Missing Input";
        }
        break;
        
        
        
    case "event-log":
        $rowsPerPage = 30;
        $offset = $rowsPerPage * ($formData["page"] - 1);
        $events = VerificationEventPersistence::getVerificationEvents(null, null, $rowsPerPage, $offset);
        
        $linkIds = array();
        foreach($events AS $event){
            $linkIds[] = $event["link_id"];
        }
        $serviceLinksForLinkedIds = LinkingPersistencyHelper::getServiceLinksForLinkedIds($linkIds);
        
        global $gw2i_linkedServices;
        foreach($events as $key => $event){
            foreach($serviceLinksForLinkedIds as $serviceLink){
                if($serviceLink["link_id"] == $event["link_id"]){
                    $serviceId = $serviceLink["service_id"];
                    $userId = $serviceLink["service_user_id"];
                    $displayName = $serviceLink["service_display_name"];
                    if(!isset($events[$key]["services"])){
                        $events[$key]["services"] = array();
                    }
                    $events[$key]["services"][$serviceId] = $displayName . " ($userId)";
                }
            }
        }
        
        $serviceList = array();
        foreach($gw2i_linkedServices as $serviceId => $service){
            $serviceList[$serviceId] = $service->getName();
        }
        
        $result["services"] = $serviceList;
        $result["events"] = $events;
            
        break;
        
        
    case "get-log":
        if(isset($formData["filename"])){
            $logName = $formData["filename"];
        } else {
            $date = date("Y-m-d");
            $logName = "log_$date.log";
        }
        $filePath = "$loggingDir/$logName";
        $logFile = fopen($filePath, "r");
        $fileSize = filesize($filePath);
        if($fileSize > 0) {
            $result["log"] = htmlentities(fread($logFile,$fileSize));
        } else {
            $result["error"] = "Could not find log file $logName";
        }

        fclose($logFile);
            
        break;
        
        
    case "collect-statistics":
        global $statistics;
        $statistics->collectStatistics();
        $result["result"] = "Success";
        break;
        
    
    case "get-statistics-world-distribution":
        global $statistics;
        $timeToSwitchToHours = 86400; //1 day in seconds
        $timeToSwitchToDays = 259200; //3 days in seconds
        $graphData = $statistics->getCombinedChartData(
                array(StatisticsPersistence::VALID_KEYS, StatisticsPersistence::TEMPORARY_ACCESS), 
                $timeToSwitchToHours,
                $timeToSwitchToDays
            );
        
        if(isset($graphData[0])){
            foreach($graphData[0] AS $key => $columnName){
                if($key > 0){
                    $split = explode(":", $columnName);
                    if($split[0] == 6){
                        $newName = "[T] ";
                    } else {
                        $newName = "";
                    }
                    $newName .= GW2DataFieldConverter::getWorldNameById($split[1]);
                    $graphData[0][$key] = $newName;
                } else {
                    $graphData[0][$key] = array("type" => "date", "label" => "Date");
                }
            }
        }
        
        $skimmedGraphData = array($graphData[0]);
        $roundedIndex = 0;
        $lastRowDate = null;
        for($i = 1; $i < count($graphData); $i++){
            $time = strtotime($graphData[$i][0]);
            $roundedTime = ceil($time / 3600) * 3600; //3600 1 hour in seconds
            $date = date("Y-m-d H:i:s", $roundedTime);

            if($time >= time() - $timeToSwitchToHours) {
                $skimmedGraphData[] = $graphData[$i];
                $roundedIndex++;
            } else if($lastRowDate != $date){
                $skimmedGraphData[] = array_merge(array($date), array_slice($graphData[$i], 1));
                $roundedIndex++;
                $lastRowDate = $date;
            } else {
                for($k = 1; $k < count($skimmedGraphData[$roundedIndex]) - 1; $k++){
                    $skimmedGraphData[$roundedIndex][$k] = $graphData[$i][$k];
                }
            }
        }
        
        $result["chart"] = $skimmedGraphData;
        
        $result["options"] = array(
            "vAxis" => array("logScale" => true)
        );
        break;
        
    
    case "get-statistics-api-calls":
        global $statistics;
        $graphData = $statistics->getCombinedChartData(
                array(StatisticsPersistence::AVERAGE_TIME_PER_KEY, StatisticsPersistence::API_ERRORS, StatisticsPersistence::API_SUCCESS),
                null,
                null,
                604800, //1 week in seconds
                2); 
         
        $series = array();
        $typeToIndex = array();
        if(isset($graphData[0])){
            foreach($graphData[0] AS $key => $columnName){
                if($key > 0){
                    $split = explode(":", $columnName);
                    switch($split[0]){
                        case StatisticsPersistence::API_ERRORS:
                            $newName = "API Errors";
                            $series[] = array("axis" => "Count");
                            $typeToIndex[StatisticsPersistence::API_ERRORS] = $key;
                            break;
                        case StatisticsPersistence::API_SUCCESS:
                            $newName = "API Successes";
                            $series[] = array("axis" => "Count");
                            $typeToIndex[StatisticsPersistence::API_SUCCESS] = $key;
                            break;
                        case StatisticsPersistence::AVERAGE_TIME_PER_KEY:
                            $newName = "Average Time Per Key";
                            $series[] = array("axis" => "TimeMS");
                            $typeToIndex[StatisticsPersistence::AVERAGE_TIME_PER_KEY] = $key;
                            break;
                        default:
                            $newName = $columnName;
                    }
                    $graphData[0][$key] = $newName;
                } else {
                    $graphData[0][$key] = array("type" => "date", "label" => "Date");
                }
            }
            $skimmedGraphData = array($graphData[0]);
            $roundedIndex = 0;
            $lastRowDate = null;
            $keysPerRun = SettingsPersistencyHelper::getSetting(SettingsPersistencyHelper::API_KEYS_PER_RUN);
            $hasNormalAPICallRun = false;
            for($i = 1; $i < count($graphData); $i++){
                $time = strtotime($graphData[$i][0]);
                $roundedTime = ceil($time / 300) * 300;
                $date = date("Y-m-d H:i:s", $roundedTime);
                
                $successes = $graphData[$i][$typeToIndex[StatisticsPersistence::API_SUCCESS]];
                $errors = $graphData[$i][$typeToIndex[StatisticsPersistence::API_ERRORS]];
                
                if($lastRowDate != $date || ($successes + $errors >= $keysPerRun && $hasNormalAPICallRun)){
                    if($lastRowDate == $date){
                        //$test = array($lastRowDate, $date);
                        $skimmedGraphData[$roundedIndex][0] = date("Y-m-d H:i:s", $time - 1);
                        $lastRowDate = $skimmedGraphData[$roundedIndex][0];
//                            $test[2] = $lastRowDate;
//                            print_r($test);
                    }
                    $skimmedGraphData[] = array_merge(array($date), array_slice($graphData[$i], 1));
                    $roundedIndex++;
                    $lastRowDate = $date;
                    $hasNormalAPICallRun = false;
                } else {
                    $totalNOld = $skimmedGraphData[$roundedIndex][$typeToIndex[StatisticsPersistence::API_SUCCESS]];
                    $skimmedGraphData[$roundedIndex][$typeToIndex[StatisticsPersistence::API_SUCCESS]] 
                            += $successes;

                    $totalNOld += $skimmedGraphData[$roundedIndex][$typeToIndex[StatisticsPersistence::API_ERRORS]];
                    $skimmedGraphData[$roundedIndex][$typeToIndex[StatisticsPersistence::API_ERRORS]] 
                            += $graphData[$i][$typeToIndex[StatisticsPersistence::API_ERRORS]];

                    $totalNNew = $successes + $errors;

                    //Calculate new average
                    $weightOld = $totalNOld / ($totalNOld + $totalNNew);
                    $weightNew = $totalNNew / ($totalNOld + $totalNNew);
                    $skimmedGraphData[$roundedIndex][$typeToIndex[StatisticsPersistence::AVERAGE_TIME_PER_KEY]] 
                            = ($weightOld * $skimmedGraphData[$roundedIndex][$typeToIndex[StatisticsPersistence::AVERAGE_TIME_PER_KEY]])
                            + ($weightNew * $graphData[$i][$typeToIndex[StatisticsPersistence::AVERAGE_TIME_PER_KEY]]);
                }
                if($successes + $errors >= $keysPerRun){
                    $hasNormalAPICallRun = true;
                }
            }
            $graphData = $skimmedGraphData;
        }
        $result["options"] = array(
            "series" => $series,
            "axes" => array(
                "y" => array(
                    "TimeMS" => array("label" => "Time (ms)"),
                    "Count" => array("label" => "Count")
                )
            ),
        );
        
        $result["chart"] = $graphData;
        break;
        
        
    case "update-settings":
        
        $settings = array();
        foreach($formData AS $settingName => $settingValue){
            if($settingName != "form"){
                $settings[$settingName] = $settingValue;
            }
        }
        $updateResult = SettingsPersistencyHelper::persistSettings($settings);
        $result["result"] = $updateResult === true ? "Success" : "Failed";
        
        break;
        
        
    default:
        $result = $_POST;
        break;
}

echo json_encode(array(
    "data" => $result
), JSON_NUMERIC_CHECK );