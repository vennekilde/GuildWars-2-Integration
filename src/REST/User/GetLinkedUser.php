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

require __DIR__.'/../RESTHelper.php';

use GW2Integration\Controller\LinkedUserController;
use GW2Integration\Exceptions\UnableToDetermineLinkId;
use GW2Integration\REST\RESTHelper;
use function GuzzleHttp\json_encode;

try{
    $linkedUser = RESTHelper::getLinkedUserFromParams();
    
    $linkedUserWithLinks = LinkedUserController::getServiceLinks($linkedUser);
    $response = array(
        "link_id" => $linkedUserWithLinks->getLinkedId(),
        "primary" => $linkedUserWithLinks->primaryServiceIds,
        "secondary" => $linkedUserWithLinks->secondaryServiceIds
    );

    echo json_encode($response);
} catch(UnableToDetermineLinkId $ex){
    echo "false";
} 