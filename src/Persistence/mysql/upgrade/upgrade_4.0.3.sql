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
/**
 * Author:  Jeppe Boysen Vennekilde
 * Created: 28-09-2016
 */

ALTER TABLE `gw2integration_characters` CHANGE `c_name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `gw2integration_characters` CHANGE `c_race` `race` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_characters` CHANGE `c_gender` `gender` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_characters` CHANGE `c_profession` `profession` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_characters` CHANGE `c_level` `level` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_characters` CHANGE `g_uuid` `guild` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `gw2integration_characters` CHANGE `c_age` `age` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_characters` CHANGE `c_created` `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `gw2integration_characters` CHANGE `c_deaths` `deaths` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_characters` CHANGE `c_title` `title` INT(11) NULL DEFAULT NULL;

ALTER TABLE `gw2integration_characters` ADD `id` INT(11) NOT NULL AUTO_INCREMENT FIRST;
ALTER TABLE `gw2integration_characters` DROP PRIMARY KEY, ADD PRIMARY KEY(`id`);
ALTER TABLE `gw2integration_characters` CHANGE `link_id` `link_id` INT(11) UNSIGNED NOT NULL;

ALTER TABLE `gw2integration_character_crafting` ADD `id` INT(11) NOT NULL FIRST;

ALTER TABLE `gw2integration_character_crafting` DROP `c_name`;
ALTER TABLE `gw2integration_character_crafting` CHANGE `cr_discipline` `discipline` TINYINT(3) UNSIGNED NOT NULL;
ALTER TABLE `gw2integration_character_crafting` CHANGE `cr_rating` `rating` INT(10) UNSIGNED NOT NULL;
ALTER TABLE `gw2integration_character_crafting` CHANGE `cr_active` `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gw2integration_character_crafting` DROP PRIMARY KEY, ADD PRIMARY KEY(`id`, `discipline`);

ALTER TABLE `gw2integration_guild_membership` CHANGE `link_id` `link_id` INT(11) UNSIGNED NOT NULL;
ALTER TABLE `gw2integration_characters` CHANGE `guild` `guild` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';