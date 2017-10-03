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

namespace GW2Integration\Persistence\Helper;

use GW2Integration\Persistence\Persistence;
use PDO;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of StatisticsPersistenceHelpter
 *
 * @author Jeppe Boysen Vennekilde
 */
class SettingsPersistencyHelper {
    const SALT = "salt";
    const API_KEY_EXPIRATION_TIME = "api_key_expiration_time";
    const API_KEYS_PER_RUN = "api_keys_per_run";
    const DEBUG_API_KEY = "debug_api_key";
    const IS_API_DOWN = "is_api_down";
    const TEAMSPEAK_BOT_ADDRESS = "teamspeak_bot_address";
    const TEMPORARY_ACCESS_EXPIRATION = "temporary_access_expiration";
    const STRICT_API_KEY_NAME = "strict_api_key_name";
    const API_KEY_NAME_PREFIX = "api_key_name_prefix";
    const ACCOUNT_LEVEL_REQUIREMENT = "account_level_requirement";
    const SETUP_WEB_PATH = "setup_web_path";
    const HOME_WORLD = "home_world";
    const LINKED_WORLDS = "linked_words";
    const SMF_LINKED_WORLD_TEMP_GROUP_PRIMARY = "smf_linked_world_temp_group_primary";
    const TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_PRIMARY = "teamspeak_linked_world_temp_group_primary";
    const TEAMSPEAK_LINKED_WORLD_TEMP_GROUP_SECONDARY = "teamspeak_linked_world_temp_group_secondary";
    const API_KEYS_BATCH_MAX_PROCESS_TIME = "api_keys_batch_max_process_time";
    
    
    public static $visibleSettings = array(
        "API Settings" => array(
            self::API_KEY_EXPIRATION_TIME,
            self::API_KEYS_PER_RUN,
            self::API_KEYS_BATCH_MAX_PROCESS_TIME,
            self::DEBUG_API_KEY,
        ),
        "Access Settings" => array(
            self::STRICT_API_KEY_NAME,
            self::API_KEY_NAME_PREFIX,
            self::ACCOUNT_LEVEL_REQUIREMENT,
            self::TEMPORARY_ACCESS_EXPIRATION
        ),
        "WvW" => array(
            self::HOME_WORLD,
            self::LINKED_WORLDS
        ),
        "MISC" => array(
            self::SETUP_WEB_PATH
        )
    );
    
    private static $settingsCache = array();
    
    /**
     * 
     * @param type $settingName
     * @return type
     */
    public static function getSetting($settingName, $useCache = true) {
        $settingValue = null;
        if($useCache && isset(static::$settingsCache[$settingName])){
            $settingValue = static::$settingsCache[$settingName];
        } else {
            global $gw2i_db_prefix;
            $preparedQueryString = 'SELECT setting_value FROM '.$gw2i_db_prefix.'integration_settings WHERE setting_name = ? LIMIT 1';
            $queryParams = array($settingName);

            $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

            $preparedStatement->execute($queryParams);
            if($preparedStatement->rowCount() > 0){
                $settingValue = $preparedStatement->fetch(PDO::FETCH_NUM)[0];
                //Cache
                static::$settingsCache[$settingName] = $settingValue;
            }
        }
        return $settingValue;
    }
    
    /**
     * 
     * @param type $settingName
     * @return type
     */
    public static function getAllSetting() {
        global $gw2i_db_prefix;
        $preparedQueryString = 'SELECT setting_name, setting_value FROM '.$gw2i_db_prefix.'integration_settings';

        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $preparedStatement->execute();
        if($preparedStatement->rowCount() > 0){
            $settings = $preparedStatement->fetchAll(PDO::FETCH_NUM);
            //Cache
            foreach($settings AS $settingData){
                static::$settingsCache[$settingData[0]] = $settingData[1];
            }
        }
        return static::$settingsCache;
    }
    
    /**
     * 
     * @param type $settingName
     * @param type $settingValue
     * @return type
     */
    public static function persistSetting($settingName, $settingValue){
        global $gw2i_db_prefix;
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'integration_settings (setting_name, setting_value)
                VALUES(?,?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value)';
        $queryParams = array(
            $settingName,
            $settingValue
        );
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);

        $result = $preparedStatement->execute($queryParams);
        if($result){
            static::$settingsCache[$settingName] = $settingValue;
        }
        return $result;
    }
    
    /**
     * 
     * @global type $gw2i_db_prefix
     * @param type $settings
     * @return type
     */
    public static function persistSettings($settings){
        global $gw2i_db_prefix;
        if(empty($settings)){
            return true;
        }
        
        $queryParams = array();
        $valuesQuery = "";
        $first = true;
        foreach($settings AS $settingName => $settingValue){
            if($first){
                $first = false;
            } else {
                $valuesQuery .= ", ";
            }
            $valuesQuery .= "(?,?)";
            $queryParams[] = $settingName;
            $queryParams[] = $settingValue;
        }
        
        $preparedQueryString = '
            INSERT INTO '.$gw2i_db_prefix.'integration_settings (setting_name, setting_value)
                VALUES '.$valuesQuery.'
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value)';
        
        $preparedStatement = Persistence::getDBEngine()->prepare($preparedQueryString);
        $result = $preparedStatement->execute($queryParams);
        if($result){
            foreach($settings AS $settingName => $settingValue){
                static::$settingsCache[$settingName] = $settingValue;
            }
        }
        return $result;
    }
    
}
