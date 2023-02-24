<?php 

	/*
		Literally, we don't want this file printing error such as:
		
		Uncaught Error: Class 'uss' not found in bla..bla..bla..
		
	*/
	
	defined( 'ROOT_DIR' ) OR DIE;
	
	events::exec("@footer"); 
	
?>
	
	<!-- [default] -->
	<script src='<?php echo core::url( ASSETS_DIR . '/js/jquery-3.6.0.min.js' ); ?>'></script>
	<script src='<?php echo core::url( ASSETS_DIR . '/js/bootstrap.bundle.min.js' ); ?>'></script>
	<script src='<?php echo core::url( ASSETS_DIR . '/js/bootbox.all.min.js' ); ?>'></script>
	<script src='<?php echo core::url( ASSETS_DIR . '/vendor/notiflix/notiflix-loading-aio-3.2.6.min.js' ); ?>'></script>
	<script src='<?php echo core::url( ASSETS_DIR . '/vendor/notiflix/notiflix-block-aio-3.2.6.min.js' ); ?>'></script>
	<script src='<?php echo core::url( ASSETS_DIR . '/vendor/toastr/toastr.min.js' ); ?>'></script>
	<script src='<?php echo core::url( ASSETS_DIR . '/js/main.js' ); ?>'></script>
	<!-- [/default] -->
	
<?php events::exec('@body::after'); ?>
		
</body>
</html>