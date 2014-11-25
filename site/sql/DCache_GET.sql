
-- ----------------------------------------------------------------------------
-- Get the donation Expiration Time for a user
--
-- @param userid  Forum userid for user, or NULL if not used.
-- @param steamid SteamID for user or NULL if not used.
-- @param weight  How many seconds one dollar is worth.
-- @returns       Donation expiration unixtime.
--
CREATE FUNCTION `DCache_GET` ( 
	userid INT, steamid BIGINT, weight FLOAT ) 
	
RETURNS INT NOT DETERMINISTIC 
READS SQL DATA 
SQL SECURITY INVOKER
BEGIN
	SET @a := 0; 
	SET userid = IFNULL(userid, -1);
	SET steamid = IFNULL(steamid, -1);

	-- `a` is the running expiry time, if the donation time is past the 
	-- expiry time, then `a` gets reset to that donation's expiry, 
	-- otherwise, the donation duration time is added to `a`.
	RETURN 
	(SELECT MAX(IF(ptime >= @a, @a := ptime+atm, @a := @a+atm))
	FROM (SELECT payment_date AS ptime, (mc_gross*exchange_rate)*weight AS atm
			FROM dopro_donations
			WHERE user_id = userid OR option_name2 = steamid
			-- not really sure what will actually happen with refunds..
			AND (payment_status = 'Completed' OR payment_status = 'Refunded')
			ORDER BY payment_date ASC
		) AS q1);
END;