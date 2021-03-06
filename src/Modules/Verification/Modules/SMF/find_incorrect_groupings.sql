DELIMITER $$
CREATE PROCEDURE gw2integration_smf_find_incorrect_groupings
(IN userId INT(11))
BEGIN
    DECLARE expirationTime INT;
    SELECT setting_value INTO expirationTime FROM gw2integration_integration_settings WHERE setting_name = 'api_key_expiration_time';

    SELECT l.link_id, m.id_member, w.group_id FROM gw2integration_user_service_links l
    LEFT JOIN gw2integration_accounts a ON a.link_id = l.link_id
    LEFT JOIN gw2integration_api_keys k ON a.link_id = k.link_id
    LEFT JOIN gw2integration_banned_accounts b ON a.a_username = b.b_username
    RIGHT JOIN farshiverpeaks.smf_members m ON l.service_user_id = m.id_member
    INNER JOIN gw2integration_world_to_service_group w ON 
        w.service_id = l.service_id
        AND (
            m.id_group = w.group_id 
            OR FIND_IN_SET(w.group_id, m.additional_groups) != 0
        )
    WHERE l.service_id = 0 AND (userId IS NULL OR userId = m.id_member) AND ((
        /* Check if user already has group */
        m.id_group = w.group_id
        /* Check if user is actually allowed to have the group*/
        AND (
                l.link_id IS NULL                                          /* Check if linking exists */
                OR b.b_username IS NOT NULL                                /* Check if banned */
                OR k.last_success < k.last_attempted_fetch - INTERVAL expirationTime SECOND /* Check if expired */
                OR l.is_primary != w.is_primary                            /* Check if link priority is wrong */
                /* Check if the user world is wrong */
        )					
    ) OR (
        /* Check if user has group as secondary group */
        FIND_IN_SET(w.group_id, m.additional_groups) != 0  
        /* Check if user is actually allowed to have the group*/         
        AND (
                l.link_id IS NULL                                          /* Check if linking exists */
                OR b.b_username IS NOT NULL                                /* Check if banned */
                OR k.last_success < k.last_attempted_fetch - INTERVAL expirationTime SECOND /* Check if expired */
                OR l.is_primary != w.is_primary                            /* Check if link priority is wrong */
        )		
    /* Ensure there isn't 2 worlds who should be linked with the same group) */
    )) AND (
        SELECT EXISTS(SELECT 1 FROM gw2integration_world_to_service_group w2 
        WHERE a.a_world = w2.world AND w.group_id = w2.group_id AND l.is_primary = w2.is_primary AND w2.service_id = 0) = 0
    )

     ORDER BY w.group_id ASC;
END $$
DELIMITER ;