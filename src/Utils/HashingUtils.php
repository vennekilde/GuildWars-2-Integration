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

namespace GW2Integration\Utils;


if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}

/**
 * Description of HashingUtils
 *
 * @author jeppe
 */
class HashingUtils {
    /**
     * Used for generating short hashes that aren't supposed to be secure from attacks like
     * bruteforce or hash collisions
     * @param string $plaintext
     * @return string
     */
    public static function generateWeakHash($plaintext, $salt = null){
        if(!isset($salt)  || $salt === null){
            global $hashingSalt;
            $salt = $hashingSalt;
        }
        $hash = md5($salt . $plaintext);
        return substr($hash, 0, 16);
    }
    
    /**
     * 
     * @param type $plaintext
     * @param type $salt If none is provided, a random one will be generated
     * @return type
     */
    public static function generateStrongHash($plaintext, $salt = null){
        $options = array();
        if(isset($salt) && $salt != null){
            $options["salt"] = $salt;
        }
        return password_hash($plaintext, PASSWORD_BCRYPT, $options);
    }
}
