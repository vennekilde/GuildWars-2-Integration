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
 * Created: 23-09-2016
 */

TRUNCATE gw2_integration_live.gw2integration_accounts;
TRUNCATE gw2_integration_live.gw2integration_api_keys;
TRUNCATE gw2_integration_live.gw2integration_user_service_links;

INSERT INTO gw2_integration_live.gw2integration_accounts (link_id,a_uuid,a_username,a_world,a_created,a_access,a_commander,a_fractal_level,a_daily_ap,a_monthly_ap,a_wvw_rank)
SELECT 	link_id, uuid, username,world,created,access,commander,fractal_level,daily_ap,monthly_ap,wvw_rank  FROM gw2_integration.gw2_accounts;

INSERT INTO gw2_integration_live.gw2integration_api_keys (link_id,api_key,api_key_name,api_key_permissions,last_success,last_attempted_fetch)
SELECT link_id,api_key,api_key_name,api_key_permissions,last_success Ascending,last_attempted_fetch  FROM gw2_integration.gw2_api_keys;

INSERT INTO gw2_integration_live.gw2integration_user_service_links (link_id, service_id, service_user_id, service_display_name, is_primary)
SELECT link_id, service_id, service_user_id, service_display_name, service_link_rank = 1  FROM gw2_integration.gw2_linked_services;