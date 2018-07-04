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

namespace GW2Integration\Events\Events;

use GW2Integration\Entity\LinkedUser;
use function GuzzleHttp\json_encode;

/**
 * Description of GW2ResponseEvent
 *
 * @author jeppe
 */
class GW2DataPrePersistEvent extends UserEvent{
    /**
     *
     * @var LinkedUser 
     */
    private $linkedUser;
    private $data;
    private $endpoint;
    
    function __construct($linkedUser, $endpoint, $data) {
        parent::__construct($linkedUser);
        $this->data = $data;
        $this->endpoint = $endpoint;
    }
    
    function getData() {
        return $this->data;
    }
    
    function setData($data) {
        $this->data = $data;
    }

    function getEndpoint() {
        return $this->endpoint;
    }
    
    public function toString() {
        return "linkedUser: $this->linkedUser, endpoint: $this->endpoint, data: {".json_encode($this->data)."}";
    }
    
    public function __toString() {
        return $this->toString();
    }
}
