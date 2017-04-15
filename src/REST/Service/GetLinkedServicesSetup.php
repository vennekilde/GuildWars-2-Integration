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

use GW2Integration\REST\RESTHelper;
use function GuzzleHttp\json_encode;

/**
 * Return an array of html code with the required input for establishing any missing links
 * 
 * Returns an empty array if no setup is required
 */

$result = array();

$servicesToLinkStr = filter_input(INPUT_POST, 'services');
$successJSFunction = filter_input(INPUT_POST, 'on-success-js-func');

if(isset($servicesToLinkStr)){
    $servicesToLink = explode(",",$servicesToLinkStr);
    
    //Establish which links are already known
    $linkedUser = RESTHelper::getLinkedUserFromParams();
    
    if($linkedUser != null){
        global $gw2i_linkedServices;
        foreach($servicesToLink AS $serviceToLink){
            //Retrieve service object
            $linkedService = $gw2i_linkedServices[$serviceToLink];
            //Check if the service link can be determined during setup
            if($linkedService->canDetermineLinkDuringSetup()){
                //Check if any check for existing primary id's can be done
                $primaryUserServiceLinks = $linkedUser->getPrimaryUserServiceLinks();
                if(!empty($primaryUserServiceLinks)){
                    //Check if the link is already known
                    if(!array_key_exists($serviceToLink, $primaryUserServiceLinks)){
                        //Add setup html
                        $result[$serviceToLink] = $linkedService->getLinkSetupHTML($successJSFunction);
                    }
                } else{
                    //Add setup html
                    $result[$serviceToLink] = htmlentities($linkedService->getLinkSetupHTML($successJSFunction));
                }
            }
        }
    }
}
echo json_encode($result);
