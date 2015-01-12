
SELECT stem, DCache_GetSteam( steamuser.userid, stem, 2678400 ) as expires5, 
			 DCache_GetSteam( steamuser.userid, stem, 535680 ) as expires1
FROM
	-- list of steam IDs
	(SELECT IF( option_name2 REGEXP '^765[0-9]+$', option_name2, steamuser.steamid ) AS stem
	FROM dopro_donations
	LEFT JOIN steamuser ON steamuser.userid = dopro_donations.user_id
	WHERE (option_name2 REGEXP '^765[0-9]+$' OR steamuser.steamid IS NOT NULL)
	GROUP BY stem ORDER BY NULL) AS stemlist
LEFT JOIN steamuser ON steamuser.steamid = stem