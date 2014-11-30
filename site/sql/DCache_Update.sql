
LOCK TABLES dopro_donations READ, dopro_donations AS dp1 READ, 
			steamuser READ, steamuser AS su2 READ, 
			UserDonationCache WRITE, SteamDonationCache WRITE;
			
TRUNCATE TABLE UserDonationCache;
TRUNCATE TABLE SteamDonationCache;

-- rebuild user donation cache (easy)
INSERT INTO UserDonationCache 
	SELECT user_id, DCache_GetForum( user_id, 2678400 ), 
					DCache_GetForum( user_id, 535680 ) 
	FROM dopro_donations AS dp1
	WHERE user_id != 0
	GROUP BY user_id;
	
-- rebuild steam donation cache
INSERT INTO SteamDonationCache
	SELECT stem, DCache_GetSteam( steamuser.userid, stem, 2678400 ) as expires1, 
				 DCache_GetSteam( steamuser.userid, stem, 535680 ) as expires5
	FROM
		-- get a list of steam IDs in the donation table
		-- using option_name2 if it is a steamid, otherwise using steamuser[forumid]
		(SELECT IF( option_name2 REGEXP '^765[0-9]+$', option_name2, su2.steamid ) AS stem
		FROM dopro_donations AS dp1
		LEFT JOIN steamuser AS su2 ON userid = user_id
		WHERE (option_name2 REGEXP '^765[0-9]+$' OR su2.steamid IS NOT NULL)
		GROUP BY stem ORDER BY NULL) AS stemlist
	LEFT JOIN steamuser ON steamuser.steamid = stem;
	
UNLOCK TABLES;
