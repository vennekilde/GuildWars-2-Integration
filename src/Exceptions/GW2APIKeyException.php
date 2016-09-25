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

namespace GW2Integration\Exceptions;

use Exception;


/**
 * Description of GW2APIKeyException
 *
 * @author jeppe
 */

class GW2APIKeyException extends GW2APIException {
    private $apiKey;
    public function __construct($message, $apiKey, $response, $httpCode, $code = 0, Exception $previous = null) {
        parent::__construct($message, $response, $httpCode, $code, $previous);
        $this->apiKey = $apiKey;
    }
    
    function getApiKey() {
        return $this->apiKey;
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->getCode()}]: {$this->getMessage()} APIKey: {$this->getApiKey()} HTTP Code: {$this->getHttpCode()} Response: {$this->getResponse()}\n";
    }
}