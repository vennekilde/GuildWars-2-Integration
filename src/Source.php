<?php

use GW2Integration\Logger\EventLogger;
use GW2Integration\Persistence\Helper\SettingsPersistencyHelper;
use GW2Integration\Processes\Cleanup;
use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

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

define("GW2Integration", "loaded");

$libraryPath = __DIR__;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * the primary database connection object
 *
 * @global resource $GLOBALS['logger']
 * @name $logger
 */
global $logger;
$loggingLevel = LogLevel::INFO;
$loggingDir = '/var/log/httpd/gw2integration';
$logger = new Logger($loggingDir, $loggingLevel, array(
    'extension' => 'log',
    'dateFormat' => 'Y-m-d G:i:s - uμ\\s'
));

require_once __DIR__.'/config.php';

foreach($gw2i_modules as $module) {
    $module->init();
}

$eventLogger = new EventLogger();
$cleanupProcess = new Cleanup();

//Retrieve hashing salt
$hashingSalt = SettingsPersistencyHelper::getSetting("salt");
//Check if a salt is actually persisted. If not, generate one
if($hashingSalt === null){
    //Generate new random salt
    $length = 64;
    $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    for($i = 0; $i < $length; $i++) {
        $random_int = mt_rand();
        $hashingSalt .= $charset[$random_int % strlen($charset)];
    }
    SettingsPersistencyHelper::persistSetting("salt", $hashingSalt);
}
