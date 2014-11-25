
LOCK TABLES dopro_donations READ, dopro_donations AS dp1 READ, 
			steamuser READ, UserDonationCache WRITE, SteamDonationCache WRITE;
			
TRUNCATE TABLE UserDonationCache;
INSERT INTO UserDonationCache 
	SELECT user_id, DCache_GET( user_id, steamuser.steamid, 2678400 ), 
					DCache_GET( user_id, steamuser.steamid, 535680 ) 
	FROM dopro_donations AS dp1
	LEFT JOIN steamuser ON user_id = userid
	WHERE user_id != 0
	GROUP BY user_id;
	
TRUNCATE TABLE SteamDonationCache;
INSERT INTO SteamDonationCache 
	SELECT option_name2, DCache_GET( steamuser.userid, option_name2, 2678400 ), 
						 DCache_GET( steamuser.userid, option_name2, 535680 ) 
	FROM dopro_donations AS dp1
	LEFT JOIN steamuser ON steamid = option_name2
	WHERE option_name2 != 0
	GROUP BY option_name2;
	
UNLOCK TABLES;
