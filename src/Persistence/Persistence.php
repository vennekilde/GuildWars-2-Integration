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

namespace GW2Integration\Persistence;

use PDO;

if (!defined('GW2Integration')) {
    die('Hacking attempt...');
}
/**
 * Description of Persistence
 *
 * @author Jeppe Boysen Vennekilde
 */
class Persistence{
    private static $instance = null;
    /**
     * 
     * @global Persistence $instance
     * @return Persistence
     */
    public static function DB(){
        if(static::$instance == null){
            static::$instance = new Persistence();
        }
        return static::$instance;
    }
    
    /**
     *
     * @var PDO 
     */
    private $db;
    
    public function __construct(){
        global $gw2i_db_engine, $gw2i_db_database, $gw2i_db_address, $gw2i_db_password, $gw2i_db_user; 
        $this->db = new PDO(
                $gw2i_db_engine.':host='.$gw2i_db_address.';dbname='.$gw2i_db_database.';charset=utf8mb4', $gw2i_db_user, $gw2i_db_password, 
                array(
                    PDO::ATTR_EMULATE_PREPARES => false, 
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
    }
    
    /**
     * 
     * @return PDO
     */
    public static function getDBEngine(){
        return static::DB()->db;
    }
}
