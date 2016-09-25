DELIMITER $$
CREATE PROCEDURE gw2integration_smf_find_missing_groupings
(IN userId INT(11))
BEGIN
    DECLARE expirationTime INT;
    SELECT setting_value INTO expirationTime FROM gw2integration_integration_settings WHERE setting_name = 'api_key_expiration_time';

    SELECT l.link_id, m.id_member, w.group_id FROM gw2integration_user_service_links l
    LEFT JOIN gw2integration_accounts a ON a.link_id = l.link_id
    LEFT JOIN gw2integration_api_keys k ON a.link_id = k.link_id
    LEFT JOIN gw2integration_banned_accounts b ON a.a_username = b.b_username
    RIGHT JOIN farshiverpeaks.smf_members2 m ON l.service_user_id = m.id_member
    INNER JOIN gw2integration_world_to_service_group w ON 
        w.service_id = l.service_id AND a.a_world = w.world AND l.is_primary = w.is_primary /* Get the group id the user is allowed to be assigned to */

    WHERE l.service_id = 0 AND (userId IS NULL OR userId = m.id_member) AND ((
        /* Check if user already has group */
        m.id_group = 0
        /* Check if user is actually allowed to have the group */
        AND (
            l.link_id IS NOT NULL 										/* Ensure link exists */
            AND b.b_username IS NULL 									/* Ensure not banned */
            AND k.last_success >= NOW() - INTERVAL expirationTime SECOND/* Ensure not expired */
            AND a.a_world = w.world 									/* Ensure the user is actually in the associated world */
            AND l.is_primary = w.is_primary								/* Ensure the world to group relation is on the same access level */
            AND FIND_IN_SET(w.group_id, m.additional_groups) = 0        /* Ensure the user doesn't have the group as an additional group */
        )							
    ) OR (
        /* Check if user has group as secondary group */
        FIND_IN_SET(w.group_id, m.additional_groups) = 0  
        /* Check if user is actually allowed to have the group*/         
        AND (
            l.link_id IS NOT NULL 										/* Ensure link exists */
            AND b.b_username IS NULL 									/* Ensure not banned */
            AND k.last_success >= NOW() - INTERVAL expirationTime SECOND/* Ensure not expired */
            AND a.a_world = w.world 									/* Ensure the user is actually in the associated world */
            AND l.is_primary = w.is_primary								/* Ensure the world to group relation is on the same access level */
            AND m.id_group != w.group_id                                /* Ensure the user doesn't have the group as the primary group */
        )								
    )) ORDER BY w.group_id ASC;
END $$
DELIMITER ;