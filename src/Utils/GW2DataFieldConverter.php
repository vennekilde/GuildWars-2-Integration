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

namespace GW2Integration\Utils;

/**
 * Description of GW2DataFieldConverter
 *
 * @author Jeppe Boysen Vennekilde
 */
class GW2DataFieldConverter {
    public static $world_names = array(
        1001 => "Anvil Rock",
        1002 => "Borlis Pass",
        1003 => "Yak's Bend",
        1004 => "Henge of Denravi",
        1005 => "Maguuma",
        1006 => "Sorrow's Furnace",
        1007 => "Gate of Madness",
        1008 => "Jade Quarry",
        1009 => "Fort Aspenwood",
        1010 => "Ehmry Bay",
        1011 => "Stormbluff Isle",
        1012 => "Darkhaven",
        1013 => "Sanctum of Rall",
        1014 => "Crystal Desert",
        1015 => "Isle of Janthir",
        1016 => "Sea of Sorrows",
        1017 => "Tarnished Coast",
        1018 => "Northern Shiverpeaks",
        1019 => "Blackgate",
        1020 => "Ferguson's Crossing",
        1021 => "Dragonbrand",
        1022 => "Kaineng",
        1023 => "Devona's Rest",
        1024 => "Eredon Terrace",
        2001 => "Fissure of Woe",
        2002 => "Desolation",
        2003 => "Gandara",
        2004 => "Blacktide",
        2005 => "Ring of Fire",
        2006 => "Underworld",
        2007 => "Far Shiverpeaks",
        2008 => "Whiteside Ridge",
        2009 => "Ruins of Surmia",
        2010 => "Seafarer's Rest",
        2011 => "Vabbi",
        2012 => "Piken Square",
        2013 => "Aurora Glade",
        2014 => "Gunnar's Hold",
        2101 => "Jade Sea [FR]",
        2102 => "Fort Ranik [FR]",
        2103 => "Augury Rock [FR]",
        2104 => "Vizunah Square [FR]",
        2105 => "Arborstone [FR]",
        2201 => "Kodash [DE]",
        2202 => "Riverside [DE]",
        2203 => "Elona Reach [DE]",
        2204 => "Abaddon's Mouth [DE]",
        2205 => "Drakkar Lake [DE]",
        2206 => "Miller's Sound [DE]",
        2207 => "Dzagonur [DE]",
        2301 => "Baruch Bay [SP]",
    );
    
    public static function getWorldNameById($worldId){
        return static::$world_names[$worldId];
    }
    
    /**
     * Convert an account access type field to an integer
     * @param string $string
     * @return integer Description
     */
    public static function getAccountAccessIdFromString($string){
        switch($string){
            case "GuildWars2":
                return 0;
            case "HeartOfThorns":
                return 1;
            case "PlayForFree":
                return 2;
            case "None":
                return 3;
        }
        return -1;
    }
    
    
    
    
}
