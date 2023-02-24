<?php 

	/*
		Literally, we don't want this file printing error such as:
		
		Uncaught Error: Class 'uss' not found in bla..bla..bla..
	*/
	
	defined( 'ROOT_DIR' ) OR DIE; 
	
?><!doctype html>
<html>
<head>
		
	<?php
		$console = base64_encode( json_encode( (object)self::$console ) );
		echo "<script>const uss = JSON.parse(atob('{$console}'));</script>\n";
	?>
	
<?php events::exec('@head::before'); ?>
	
	<!-- [default] -->
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<link rel='stylesheet' href='<?php echo core::url( ASSETS_DIR . '/css/bootstrap.min.css' ); ?>'>
	<link rel='stylesheet' href='<?php echo core::url( ASSETS_DIR . '/css/animate.min.css' ); ?>'>
	<link rel='stylesheet' href='<?php echo core::url( ASSETS_DIR . '/vendor/fontawesome-5.15.4/css/all.min.css' ); ?>'>
	<link rel='stylesheet' href='<?php echo core::url( ASSETS_DIR . '/vendor/toastr/toastr.min.css' ); ?>'>
	<link rel='stylesheet' href='<?php echo core::url( ASSETS_DIR . '/css/main.css' ); ?>'>
	<!-- [/default] -->
	
<?php events::exec('@head::after'); ?>
	
</head>

<body <?php if( is_array(uss::$global['body_attrs'] ?? null) ) echo core::array_to_html_attrs( uss::$global['body_attrs'] ); ?>>
	
<?php events::exec("@body::before"); ?>
	
	