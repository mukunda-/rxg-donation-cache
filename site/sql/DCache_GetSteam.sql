
-- ----------------------------------------------------------------------------
-- Get the donation Expiration Time for a steam id
--
-- @param userid  Forum userid for steamid, or NULL if not found.
-- @param steamid SteamID for user.
-- @param weight  How many seconds one dollar is worth.
-- @returns       Donation expiration unixtime.
--
CREATE FUNCTION `DCache_GetSteam` ( 
	userid INT, steamid BIGINT, weight FLOAT ) 
	
RETURNS INT NOT DETERMINISTIC 
READS SQL DATA 
SQL SECURITY INVOKER
BEGIN
	SET @a := 0;

	RETURN 
	(SELECT MAX(IF(ptime >= @a, @a := ptime+atm, @a := @a+atm))
	FROM (SELECT payment_date AS ptime, (mc_gross*exchange_rate)*weight AS atm
			FROM dopro_donations
			WHERE (option_name2 = steamid) OR (option_name2 NOT REGEXP '765[0-9]+' AND userid=user_id)
			AND (payment_status = 'Completed' OR payment_status = 'Refunded')
			ORDER BY payment_date ASC
		) AS q1);
END;