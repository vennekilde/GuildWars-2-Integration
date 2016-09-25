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

/**
 * Description of GW2ServerLoyalityEvent
 *
 * @author jeppe
 */

class GW2ServerLoyalityEvent extends Event{
    private $userId;
    private $oldWorldId;
    private $newWorldId;
    private $validTo;
    private $forceRefreshAccess;
    
    function __construct($userId, $oldWorldId, $newWorldId, $validTo, $forceRefreshAccess = false) {
        $this->userId = $userId;
        $this->oldWorldId = $oldWorldId;
        $this->newWorldId = $newWorldId;
        $this->validTo = $validTo;
        $this->forceRefreshAccess = $forceRefreshAccess;
    }

    function getUserId() {
        return $this->userId;
    }

    function getOldWorldId() {
        return $this->oldWorldId;
    }

    function getNewWorldId() {
        return $this->newWorldId;
    }
    
    function getValidTo(){
        return $this->validTo;
    }
    
    function getForceRefreshAccess() {
        return $this->forceRefreshAccess;
    }
}
