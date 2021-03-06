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
 * Description of APISyncCompleted
 *
 * @author jeppe
 */
class APISyncCompleted extends Event{

    private $timeEnded;
    private $timeStarted;
    private $successfulSyncs;
    private $attemptedKeySyncs;

    public function __construct($attemptedKeySyncs, $successfulSyncs, $timeStarted, $timeEnded) {
        $this->attemptedKeySyncs = $attemptedKeySyncs;
        $this->successfulSyncs = $successfulSyncs;
        $this->timeStarted = $timeStarted;
        $this->timeEnded = $timeEnded;
    }
    
    public function getTimeEnded() {
        return $this->timeEnded;
    }

    public function getTimeStarted() {
        return $this->timeStarted;
    }

    public function getSuccessfulSyncs() {
        return $this->successfulSyncs;
    }

    public function getAttemptedKeySyncs() {
        return $this->attemptedKeySyncs;
    }
    
    public function getFailedSyncs(){
        return $this->attemptedKeySyncs - $this->successfulSyncs;
    }
    
    public function getTimePassed(){
        return $this->timeEnded - $this->timeStarted;
    }
    
    public function getAvgTimePerKey(){
        return $this->getTimePassed() / $this->getAttemptedKeySyncs();
    }

    public function __toString() {
        return "APISyncCompleted {attemptedKeySyncs: ".$this->getAttemptedKeySyncs().", successes: ".$this->getSuccessfulSyncs().", failed: ".$this->getFailedSyncs().", timePassed: ".$this->getTimePassed()."ms, avgTimePerKey: ".$this->getAvgTimePerKey().", timeStarted: ".$this->getTimeStarted()." UNIX TIMESTAMP, timeEnded: ".$this->getTimeEnded()." UNIX TIMESTAMP}";
    }
}
