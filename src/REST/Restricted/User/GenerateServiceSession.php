<?php

use GW2Integration\Controller\ServiceSessionController;

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

require __DIR__.'/../../RestrictedRESTHelper.php';

$response = array();

//Parse input
$sessionUserId = filter_input(INPUT_GET, 'user-id');
$sessionId = filter_input(INPUT_GET, 'service-id');
$sessionUserIp = filter_input(INPUT_GET, 'ip');
$sessionUserDisplayName = filter_input(INPUT_GET, 'displayname');
$isPrimary = filter_input(INPUT_GET, 'is-primary');

if(isset($sessionUserId) && isset($sessionId) && isset($sessionUserIp)){
    $sessionHash = ServiceSessionController::createServiceSession(
            $sessionUserId, 
            $sessionId, 
            $sessionUserIp, 
            isset($sessionUserDisplayName) ? $sessionUserDisplayName : null, 
            isset($isPrimary) ? (($isPrimary == "false" ||  $isPrimary == "0") ? false : true) : true );
    $response = array("session" => $sessionHash);
} else {
    $response = array("error" => "Missing params");
}

echo json_encode($response);