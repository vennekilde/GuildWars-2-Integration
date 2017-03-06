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

/**
 * Description of VerificationStatus
 *
 * @author Jeppe Boysen Vennekilde
 */
class GW2Schema {
    
    /** @var GW2Schema  */
    public static $account;
    
    /** @var GW2Schema  */
    public static $characters;
    
    /** @var GW2Schema  */
    public static $characters_crafting;
    
    private static $initialized = false;
    
    public static function init(){
        if(self::$initialized){
            return;
        } else {
            self::$initialized = true;
        }
        
        self::$account = new GW2Schema("account",
            array(
                "link_id",
                "id",
                "age",
                "name",
                "world",
                "created",
                "access",
                "commander",
                "fractal_level",
                "daily_ap",
                "monthly_ap",
                "wvw_rank"
            )
        );
        
        self::$characters = new GW2Schema("characters",
            array(
                "id",
                "link_id",
                "name",
                "race",
                "gender",
                "profession",
                "level",
                "guild",
                "age",
                "created",
                "deaths",
                "title"
            )
        );
        
        self::$characters_crafting = new GW2Schema("character_crafting",
            array(
                "id",
                "discipline",
                "rating",
                "active",
            )
        );
    }
    
    /**
     * 
     * @param type $data
     * @param \GW2Integration\Persistence\GW2Schema $schema
     * @return type
     */
    public static function getSupportedColumns($data, GW2Schema $schema){
        return array_intersect_key($data, array_flip($schema->getColumns())); 
    }

    private $columns;
    private $tableName;
    
    public function __construct($tableName, $columns) {
        $this->tableName = $tableName;
        $this->columns = $columns;
    }
    
    public function getTableName() {
        return $this->tableName;
    }
    
    public function getColumns() {
        return $this->columns;
    }
}

GW2Schema::init();