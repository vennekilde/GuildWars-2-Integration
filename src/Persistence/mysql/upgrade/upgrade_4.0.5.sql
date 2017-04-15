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
 * Created: Apr 15, 2017
 */

ALTER TABLE gw2integration_accounts CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_api_keys CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_banned_accounts CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_characters CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_character_crafting CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_guilds CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_guild_membership CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_integration_settings CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_linked_user_sessions CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_statistics CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_user_service_links CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_verification_log CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE gw2integration_world_to_service_group CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE gw2integration_guild_membership ADD g_rank VARCHAR(64) NULL AFTER g_representing;
ALTER TABLE gw2integration_guild_membership ADD g_member_since TIMESTAMP NULL AFTER g_rank;

CREATE TABLE `gw2integration_guild_ranks` (
    `g_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `gr_name` varchar(32) COLLATE utf8_general_ci NOT NULL,
    `gr_order` int(11) NOT NULL,
    `gr_permissions` varchar(1024) NOT NULL,
    `gr_icon` varchar(1024) NOT NULL,
    PRIMARY KEY (`g_uuid`,`gr_name`),
    FOREIGN KEY (`g_uuid`) REFERENCES gw2integration_guilds.g_uuid  ON DELETE CASCADE
);
