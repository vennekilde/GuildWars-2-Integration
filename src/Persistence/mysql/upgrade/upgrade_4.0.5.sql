/* 
 * The MIT License
 *
 * Copyright 2017 Jeppe Boysen Vennekilde.
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
/**
 * Author:  Jeppe Boysen Vennekilde
 * Created: Mar 8, 2017
 */


ALTER TABLE `gw2integration_characters` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

CREATE TABLE `gw2integration_account_data_ext` ( 
    `link_id` INT(11) NOT NULL , 
    `deaths` INT NOT NULL , 
    `playtime` INT NOT NULL,
	PRIMARY KEY (link_id)
)ALTER TABLE `gw2_integration_live`.`gw2integration_statistics` ADD UNIQUE (`rid`, `type`);

ALTER TABLE `gw2_integration_live`.`gw2integration_accounts` 
CHANGE COLUMN `a_access` `a_access` VARCHAR(32) NOT NULL DEFAULT '-1' ;
