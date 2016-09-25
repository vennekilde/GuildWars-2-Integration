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
 * Created: 22-04-2016
 */
CREATE TABLE IF NOT EXISTS gw2integration_integration_settings (
    setting_name VARCHAR(255) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
	PRIMARY KEY (setting_name)
);

CREATE TABLE IF NOT EXISTS gw2integration_user_service_links (
    link_id INT(11) NOT NULL, 
    service_user_id VARCHAR(64) NOT NULL, 
    service_id INT(11) NOT NULL, 
    service_display_name VARCHAR(64) NOT NULL DEFAULT 'Unknown Display Name', 
    is_primary TINYINT(1) NOT NULL, 
	PRIMARY KEY (service_user_id, service_id)
);

CREATE TABLE `gw2integration_world_to_service_group` (
    `world` INT(11) NOT NULL,
    `service_id` INT(11) NOT NULL,
    `group_id` VARCHAR(64) NOT NULL,
    `is_primary` TINYINT(1) NOT NULL,
    PRIMARY KEY (`world`,`service_id`,`is_primary` )
);

CREATE TABLE IF NOT EXISTS gw2integration_api_keys (
	link_id int(11) NOT NULL,
    api_key CHAR(128) NOT NULL UNIQUE,
    api_key_name varchar(255) NOT NULL,
    api_key_permissions varchar(1024) NOT NULL,
    last_success TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    last_attempted_fetch TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY (link_id),
    FOREIGN KEY (link_id) REFERENCES gw2integration_accounts(link_id)
);

CREATE TABLE IF NOT EXISTS gw2integration_api_statistics (
    `rid` int(11) NOT NULL AUTO_INCREMENT,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `value` int(11) NOT NULL,
    `type` int(11) NOT NULL,
    PRIMARY KEY (rid)
);

CREATE TABLE IF NOT EXISTS `gw2integration_verification_log` (
    `rid` int(11) NOT NULL AUTO_INCREMENT,
    `link_id` int(11) NOT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `event` int(11) NOT NULL,
    `value` varchar(255) NOT NULL,
    PRIMARY KEY (rid),
    FOREIGN KEY (link_id) REFERENCES gw2integration_accounts(link_id)
);

CREATE TABLE IF NOT EXISTS `gw2integration_linked_user_sessions` (
    `rid` int(11) NOT NULL AUTO_INCREMENT,
    `session` VARCHAR(128) NOT NULL,
    `session_ip` VARCHAR(45) NOT NULL,
    `service_user_id` int(11) NOT NULL,
    `service_id` int(11) NOT NULL,
    `service_display_name` VARCHAR(64) NOT NULL,
    `is_primary` TINYINT(1) NOT NULL,
    `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (rid)
);
/*CREATE TABLE IF NOT EXISTS `gw2integration_temporary_access` (
    `rid` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `world` int(11) NOT NULL,
    `type` int(11) NOT NULL,
    `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (rid)
);*/




/*********************************/
/**  Guild Wars 2 Data tables   **/
/*********************************/

CREATE TABLE IF NOT EXISTS gw2integration_accounts (
    link_id INT NOT NULL AUTO_INCREMENT, 
    a_uuid CHAR(64) NOT NULL UNIQUE,
    a_username varchar(255) NOT NULL UNIQUE,
    a_world int(11) NOT NULL,
    a_created TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    a_access TINYINT NOT NULL,
    a_commander TINYINT(1) NOT NULL ,
    a_fractal_level MEDIUMINT NOT NULL DEFAULT 0,
    a_daily_ap int(11) NOT NULL DEFAULT 0,
    a_monthly_ap int(11) NOT NULL DEFAULT 0,
    a_wvw_rank int(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (link_id)
);

CREATE TABLE IF NOT EXISTS `gw2integration_banned_accounts` (
    `b_ban_id` int(11) NOT NULL AUTO_INCREMENT,
    `b_username` varchar(255) NOT NULL UNIQUE,
    `b_reason` int(11) NOT NULL,
    `b_banned_by` int(11) NOT NULL,
    `b_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (b_banned_by) REFERENCES gw2integration_accounts(link_id),
    PRIMARY KEY (b_ban_id)
);

CREATE TABLE IF NOT EXISTS `gw2integration_guilds` (
    `g_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `g_name` varchar(42) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `g_tag` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `g_last_synched` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`g_uuid`),
    UNIQUE KEY `g_name` (`g_name`)
);

CREATE TABLE IF NOT EXISTS `gw2integration_guild_membership` (
    `link_id` int(1) unsigned NOT NULL,
    `g_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `g_representing` bit(1) NOT NULL DEFAULT b'0',
    PRIMARY KEY (`link_id`,`g_uuid`),
    FOREIGN KEY (link_id) REFERENCES gw2integration_accounts(link_id)
);

CREATE TABLE `gw2integration_characters` (
    `c_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
    `link_id` int(1) unsigned NOT NULL,
    `c_race` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `c_gender` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `c_profession` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `c_level` tinyint(3) unsigned NOT NULL DEFAULT '0',
    `g_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `c_age` int(10) unsigned NOT NULL DEFAULT '0',
    `c_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `c_deaths` int(10) unsigned NOT NULL DEFAULT '0',
    `c_title` int(11) DEFAULT NULL,
    PRIMARY KEY (`c_name`),
    FOREIGN KEY (link_id) REFERENCES gw2integration_accounts(link_id)
);

CREATE TABLE `gw2integration_character_crafting` (
    `c_name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
    `cr_discipline` tinyint(3) unsigned NOT NULL,
    `cr_rating` int(10) unsigned NOT NULL,
    `cr_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`c_name`,`cr_discipline`)
);

