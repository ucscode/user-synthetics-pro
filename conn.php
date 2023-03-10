<?php 

define( "DB_CONNECT", TRUE ); // `FALSE` - if you don't intend to access database;


// -------- [{ Manage DataBase Configuration }] ------------


if( $_SERVER['SERVER_NAME'] == 'localhost' ):
	
	# --- [{ FOR LOCALHOST ONLY }] ---
	
	define( "DB_HOST", "localhost" );
	define( "DB_USER", 'root' );
	define( "DB_PASSWORD", '' );
	define( "DB_NAME", 'uss_cbt' );

else:
	
	# --- [{ FOR SERVER HOST ONLY }] ---
	
	define( "DB_HOST", "localhost" ); // leave this as localhost, it works on all server (if i'm not wrong)
	define( "DB_USER", '' );
	define( "DB_PASSWORD", '' );
	define( "DB_NAME", '' );

endif;


// --------- [{ DataBase Table Prefix }] ----------

define( "DB_TABLE_PREFIX", 'uss' );


