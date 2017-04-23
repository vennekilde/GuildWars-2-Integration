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
 * Created: Apr 23, 2017
 */
DELIMITER $$
CREATE PROCEDURE gw2integration_smf_find_not_linked_members
(IN userId INT(11))
BEGIN
    SELECT m.id_member, w.group_id FROM farshiverpeaks.smf_members m
    INNER JOIN gw2_integration_live.gw2integration_world_to_service_group w ON 
        m.id_group = w.group_id 
        OR FIND_IN_SET(w.group_id, m.additional_groups) != 0
        
    WHERE (userId IS NULL OR userId = m.id_member)
   		AND m.id_member NOT IN (
            SELECT l.service_user_id FROM gw2_integration_live.gw2integration_user_service_links l 
            INNER JOIN gw2_integration_live.gw2integration_accounts a ON a.link_id = l.link_id 
            INNER JOIN gw2_integration_live.gw2integration_api_keys k ON k.link_id = l.link_id 
            WHERE l.service_id = 0
        );
END $$
DELIMITER ;