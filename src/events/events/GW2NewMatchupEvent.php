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
 * Description of GW2NewMatchup
 *
 * @author jeppe
 */
class GW2NewMatchupEvent extends Event{
    private $matchId;
    private $redWorldId;
    private $blueWorldId;
    private $greenWorldId;
    private $startTime;
    private $endTime;
    
    function __construct($matchId, $redWorldId, $blueWorldId, $greenWorldId, $startTime, $endTime) {
        $this->matchId = $matchId;
        $this->redWorldId = $redWorldId;
        $this->blueWorldId = $blueWorldId;
        $this->greenWorldId = $greenWorldId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    function getMatchId() {
        return $this->matchId;
    }

    function getRedWorldId() {
        return $this->redWorldId;
    }

    function getBlueWorldId() {
        return $this->blueWorldId;
    }

    function getGreenWorldId() {
        return $this->greenWorldId;
    }

    function getStartTime() {
        return $this->startTime;
    }

    function getEndTime() {
        return $this->endTime;
    }
    
    public function __toString() {
        return "[matchId -> $this->matchId, redWorldId -> $this->redWorldId, blueWorldId -> $this->blueWorldId, greenWorldId -> $this->greenWorldId, startTime -> $this->startTime, endTime -> $this->endTime]";
    }
}
