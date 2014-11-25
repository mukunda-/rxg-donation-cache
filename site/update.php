<?php
 
// update.php?action=update_all
//   update all entries
// update.php?action=setup
//   setup database.
//
// must also have &password=<password>

require_once 'config.php';

CheckArgs( 'password', 'action' );
if( $_GET['password'] != Config::ACCESSCODE ) exit( 'error.' );

require_once 'rxgdonationcache.php';

$methods = array();

date_default_timezone_set( 'America/Chicago' );

/** ---------------------------------------------------------------------------
 * Check if a list of args are present in a GET request, and terminate the
 * script if not.
 *
 * e.g. CheckArgs( 'param1', 'param2' )
 *
 */
function CheckArgs() {
	$args = func_get_args();
	foreach( $args as $arg ) {
		if( !isset( $_GET[$arg] ) ) exit( 'error' );
	}
}

/**
 * Format a timestamp
 */
function LogStamp() {
	$time = date( 'm/d/y h:i:s' );
	return "[$time]";
}

/** ---------------------------------------------------------------------------
 * Write a line to the log file, prefixed by a timestamp and the 
 * remote address.
 */
function WriteLog( $line ) {
	if( Config::LOGFILE == '' ) return;
	
	file_put_contents( 
		Config::LOGFILE, 
		LogStamp() . " {$_SERVER['REMOTE_ADDR']} | $line\r\n", 
		FILE_APPEND );
	
}
/*
//-----------------------------------------------------------------------------
$methods[ 'update_stem' ] = function () {
	CheckArgs( 'steamid' );
	$steamid = SteamID::Parse( $_GET['steamid'] );
	if( $steamid === FALSE ) exit( 'error' );
	
	WriteLog( "Update Stem: " . $steamid->Format( SteamID::FORMAT_STEAMID32 ) 
		. " / " . $steamid->Format( SteamID::FORMAT_STEAMID64 ) );
	RXGDonationCache::UpdateStem( $steamid );
};

//-----------------------------------------------------------------------------
$methods[ 'update_userid' ] = function () {
	CheckArgs( 'userid' );
	
	$userid = $_GET['userid'];
	WriteLog( "Update Userid: $userid" );
	
	RXGDonationCache::UpdateUser( $_GET['userid'] );
};*/

//-----------------------------------------------------------------------------
$methods[ 'update_all' ] = function () { 
	WriteLog( "Running Update All." );
	RXGDonationCache::UpdateAll();
};

//-----------------------------------------------------------------------------
$methods[ 'setup' ] = function () {
	WriteLog( "Running Setup." );
	RXGDonationCache::SetupDatabase( true );
};

//-----------------------------------------------------------------------------

// find method
$action = $_GET['action']; 
if( !isset( $methods[$action] ) ) exit( 'error' ); 
$method = $methods[$action];

try {
	// execute 
	$method();
	
} catch( Exception $e ) {
	// and catch errors
	WriteLog( print_r($e,true) );
	exit( 'error.' );
}

exit( 'okay.' );

?>