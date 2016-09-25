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

require __DIR__.'/../../RestrictedRESTHelper.php';

use GW2Integration\Persistence\Helper\LinkingPersistencyHelper;
use function GuzzleHttp\json_encode;

global $gw2i_linkedServices;

$attributeName = filter_input(INPUT_GET, 'name');
$attributeValue = filter_input(INPUT_GET, 'value');
$serviceUserId = filter_input(INPUT_GET, 'user-id');
$serviceId = filter_input(INPUT_GET, 'service-id');

$response = array();

if(isset($attributeName) && isset($serviceUserId) && isset($serviceId)){
    if(isset($attributeValue)){
        //get null, false, etc to be the correct type
        $attributeValue = json_encode(json_decode($attributeValue));
    }
    LinkingPersistencyHelper::setAttribute($serviceUserId, $serviceId, $attributeName, $attributeValue);
    $response["result"] = "success";
} else {
    $response["error"] = "Missing params";
}

echo json_encode($response);