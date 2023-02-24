<?php 

/*

	This is the most important class file in user synthetics;
	
	It controls the display, configuration and other properties of user synthetics
	
	---- FOR EXAMPLE ---
	
	- user synthetics tries to avoid making use of PHP's default global variables to store information.
	
	- rather than using super `$GLOBALS` variable, user synthetics uses `uss::$global`
	
	- since the `uss` class is static, all it's property and methods can be accessed globally 
	- unless the property is not public!
	
	--- [ THAT SAID! LET'S DO THIS ] ---
	
*/

class uss {
	
	private static $platform = 'User Synthetics';
	
	/*
		- User synthetics uses [ PHP 7.4+ ] because lower version does not permit typed properties
		
		- Typed properties are very necessary to prevent modules from changing the type of important properties such as `uss::$global`
	*/
	
	
	# uss::$global - This is user synthetics' alternative to the PHP $GLOBALS variable
	
	public static array $global = array();
	
	
	/*
		- uss::$console is a private variable.
		
		- It is used to pass variable from PHP into javascript environment
		
		- The property can be easily updated using `uss::console()` & `uss::remove_console()` public methods
	*/
	
	private static $console = array();
	
	
	/*
		- uss::$focus_list stores a list of focused path;
		
		- A "focused path" is a `URL` that has been captured to perform a specific task - only when that URL is visited!
		
		- Focus path uses regular expression, making it easy to capture or focus on a range of URLs
	*/
	
	private static $focus_list = array();
	private static $focused;
	
	
	/*
		`uss::$viewing` determines whether the front-end page has been displayed.
		
		- This will avoid `USS` from printing HTML Document on the browser more than once!
		
		- As printing multiple times will create multiple <!doctype> declaration, multiple header, multiple footer etc!
	*/
	
	private static $viewing = false;
	
	
	/*
		- Checking if the USS has gone through the initialization process;
		
		- 	Since uss is mostly based on the use of static properties and methods,
			We cannot use the `__constructor()` magic method;
			Hence, the `__init()` method servers as an alternative and we should avoid calling the `__init()` twice!
	*/
	
	private static $init = false;
	
	
	
	public static function __init() {
		
		// If the USS has been initialized, ignore the call to this method;
		
		if( self::$init ) return;
		
		
		/*
			- User Synthetics is major built up on events!
			
			- The priority of each event is indicated by the event ID
			
			- Hence, user synthetics has chosen `(float)-0.5` to become it's default event ID
		*/
		
		define( 'EVENT_FLOAT', -0.5 );
		
		
		/* 
			- Prepare the essential variables required for the formation of user synthetics;
			- These variables may be overridden by other modules
		*/
		
		self::__vars();
		
		
		/*
		
			- Establish database connection!
			
			- Available in the `conn.php` file
			
			- If DB_CONNECT constant is set to false, database connection will be ignored
			
			Database connect may not be necessary for single landing pages that only displays information
			
			But for larger projects, I can't find a good reason to disconnect database connection
			
		*/
		
		self::__connect();
		
		
		/*
			!initialize session;
			
			- COOKIE IS GREAT! BUT PHP $_SESSION is important in here!
			
			Don't like sessions? Then *** DELETE USER SYNTHETICS *** ( !JUST KIDDING )
		*/
		
		self::__session();
		
		
		// End Za Initialization
		
		self::$init = true;
		
	}
	
	
	// ---------------- PRIVATE: [{ Methods below are not accessible }] ------------------
	
	
	private static function __connect() {
		
		/*
			- Now! Let's Manage DataBase Connection!
		*/
		
		if( DB_CONNECT ) {
			
			/*
				- Save MYSQLI Query into uss::$global property
				
				- Oh! That reminds me. If you use PDO, you'll have to declare your own connection!
				
				- somewhat like `uss::$global['pdo'] = ...`
				
			*/
			
			self::$global['mysqli'] = @new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
			
			// if database connection fails;
			
			if( self::$global['mysqli']->connect_errno ) {
				
				// display error message;
				
				self::view(function() {
					$html = sprintf("
						<div class='px-3 py-2 my-2 text-center'>
							<img class='d-block mx-auto mb-4' src='%s' alt='' height='76'>
							<h1 class='display-6 fw-light mb-3'>Database Connection Failed</h1>
							<p class='text-danger' style='font-family: monospace;'>This might have happened because the MYSQL server has not yet started. Otherwise:</p>
							<div class='col-lg-6 mx-auto text-muted'>
								<div class='fw-light mb-4 border-top pt-4'>
									<p>&mdash; Confirm that the information provided in your <strong>conn.php</strong> file is correct.</p>
									<p>&mdash; Ensure that the user have sufficient permission to manage database engine</p>
									<p>&mdash; Create database manually if it does not exist (PHPMyAdmin)</p>
								</div>
								<div class='border-top border-bottom mb-3'>
									<div class='py-4'>
									<p class='fs-5'>Need Help?</p>
									<a type='button' class='btn btn-primary px-4 gap-3' href='mailto:uche23mail@gmail.com'>
										Contact Developer
									</a>
									</div>
								</div>
								<div class='mb-2'>Created by <a href='%s' target='_blank'>UCSCODE</a> . &copy; 2022 </div>
							</div>
						</div>
					", self::$global['icon'], 'https://ucscode.me');
					echo $html;
				});
				
				exit;
				
			} else {
				
				/*
					- Create DataBase Option Table!
					
					This can be used for site configuration!
					
					Pairs class is awesome! Trust me...
				*/
				
				$__options = new pairs( self::$global['mysqli'], DB_TABLE_PREFIX . "_options" );
				
				self::$global['options'] = $__options;
				
			};
			
			/*
				- Yeah! Great!
				- I see you've decided to turn off the database connection!
			*/
			
		} else self::$global['mysqli'] = self::$global['options'] = null;
		
	}
	
	
	# ------------ [{ Create Unique Session ID }] -----------
	
	private static function __session() {
		
		// let's start the session!
		
		if( empty(session_id()) ) session_start();
		
		/* Create a unique visitor session ID */
		
		if( empty($_SESSION['uss_session_id']) || strlen($_SESSION['uss_session_id']) < 50 ) {
			$_SESSION['uss_session_id'] = core::keygen(mt_rand(50, 80), true);
		};
		
		/* - Unique Device ID; */
		
		if( empty($_COOKIE['ussid']) ) {
			$time = (new DateTime())->add( (new DateInterval("P6M")) );
			setrawcookie( 'ussid', core::keygen(20), $time->getTimestamp(), '/' );
		};
		
	}
	
	
	# ----------- [{ Reset Essencial Variables }] ------------
	
	public static function __vars() {
		
		/* 
			!!!validate get request;
			
			- Every request are sent to the `index.php` file as controlled by Apache Rewrite Rule
			
			- So! URL paths are stored in $_GET variable as `query` index!
			
			The indexes can be easily accessible using the `uss::query()` method;
			
		*/
		
		$_GET['query'] = ($_GET['query'] ?? null);
		
		
		/*
			!Get Default Content
		*/
		
		self::$global['icon'] = core::url( ASSETS_DIR . '/images/origin.png' );
		self::$global['title'] = self::$platform;
		self::$global['tagline'] = "The most powerful content management system &star; " . ((new DateTime())->format('Y'));
		self::$global['description'] = "Ever thought of how to make your programming life easier in developing website? \nUser Synthetics offers the best solution for a simple programming lifestyle";
		
		
		/*
			!Open Graph:
			This will print opengraph meta onto the header!
		*/
		
		self::$global['opengraph'] = array();
		
		
		/*
			body attributes;
			-------------------
			This allows you to add attribute to the <body/> tag
		*/
		
		self::$global['body_attrs'] = array(
			"class" => 'uss'
		);
		
	}
	
	
	// -------------------- PUBLIC: [{ Methods below are accessible }] --------------------
	
	
	# ------ [{ Display The Dashboard }] -------
	
	public static function view( ?callable $content = null ) {
		
		/* 
			- It's good to check the view status before printing 
			- uss will never print it's user interface unto the browser twice
		*/
		
		if( is_null($content) || self::$viewing ) return self::$viewing;
		
		/*
			- save platform name to javascript!
			- accessible by `<script>uss.platform</script>
		*/
		
		self::console( 'platform', self::$platform );
		
		
		# OUTPUT THE HEADER!
		
		require VIEW_DIR . '/header.php';
		
		
		# DISPLAY THE CONTENT 
		
		$content();
		
		
		# OUTPUT THE FOOTER!
		
		require VIEW_DIR . '/footer.php';
		
		
		# CHANGE THE VIEW STATUS;
		
		self::$viewing = true;
		
	}
	
	
	# -------- [{ Focus on a specific URL Path }] ---------
		
	public static function focus( string $path, callable $func ) {
		
		
		/*
		
			EXAMPLE:
			
			uss::focus( "users/profile", function() {
				
				- This closure will work only if domain is directly followed by `users/profile` 
				
				://domain.com/users/profile - [ will work ]
				
				://domain.com/user/profile - [ will not work ]
				
				://domain.com/users/profile2 - [ will not work ]
				
			});
			
		*/
		
		$simplify = function($request) {
			// convert to a simple & valid path;
			return implode("/", array_filter(array_map('trim', explode("/", $request))));
		};
		
		
		// simplify the focus path & url string;
		
		$focus = $simplify($path);
		$query = $simplify($_GET['query']);
		
		
		/*
			- Check if they match;
			
			- Test for regular expression of the Path
		*/
		
		$expression = '~^' . $focus . '$~';
		
		
		/*
		
			- Save the focus string into the focus_list!
			
			- Very helpful for debugging
			
		*/
		
		if( !in_array($focus, self::$focus_list) ) {
			
			$key = array_search( __FUNCTION__, array_column( debug_backtrace(), 'function' ) );
			
			$trace = debug_backtrace()[$key];
			
			self::$focus_list[] = array(
				"focus" => $focus,
				"file" => $trace['file']
			);
			
		}
		
		
		/*
			- It is very unhealthy to create a functional code without focus!
			
			- Unless it's necessary for the code to run everywhere without condition!
			
			- Nonetheless, the `config.php` file can be required anywhere
			
			- Especially in files that run through ajax without being part of the system itself
			
			Hence:
				
				- When the `config.php` file is required inappropriately!
				
				- All focused request will be seized to prevent unwanted and irrelevant outputs
				
				- However, the condition can be changed if `USE_FOCUS` constant is defined!
			
		*/
		
		
		# PERMIT ONLY THE APPROPRIATE FOCUS - ONE COMING FROM THE MAIN INDEX FILE
		
		$succession = ( core::rslash( $_SERVER['SCRIPT_FILENAME'] ) === core::rslash( ROOT_DIR . "/index.php" ) );
		
		
		# UNLESS A "USE_FOCUS" CONSTANT IS DEFINED!
		
		if( !$succession && defined('USE_FOCUS') ) $succession = true;
		
		
		/*
			Test the Focused Path compared to the Current URL
		*/
		
		if( preg_match( $expression, $query ) && $succession ) {
		
			self::$focused = $expression;
			
			// Then execute the focused callable if the focus matches the URL Path!
			
			$func();
		
		};
		
	}
	
	
	/*
		- Get the current reflecting focus path
		
		- Or pass `TRUE` as an argument to get a list of a registered focus path
	*/
	
	public static function get_focus( bool $single = false ) {
		return $single ? self::$focus_list : self::$focused;
	}
	
	
	/*
	
		THE URL:
		
			https://your-uss-domain.com/path/to/where/you/wannt/be
		
		IS EQUIVALENT TO:
		
			https://your-uss-domain.com?query=path/to/where/you/wannt/be
		
		
		NOW:
		
			`uss::query()` splits the query string and returns an array
		
		EXAMPLE: 
		
			`uss::query()`   ==  array( 'path', 'to', 'where', 'you', 'wanna', be );
		
			`uss::query(0)`  ==  "path"
			`uss::query(3)`  ==  "you"
			`uss::query(8)`  ==  NULL
			
		THAT SAID:
			
			- You're better off using `uss::query()` method than trying to decode the query manually
		
	*/
	
	public static function query( ?int $index = null ) {
		$query = array_map('trim', explode("/", $_GET['query']));
		return is_numeric($index) ? ($query[$index] ?? null) : $query;
	}
	
	
	/*
	
		Nothing important here!
		
		PHP Default Functions such as:
			
			- htmlspecialchars 
			- htmlentities 
			
		Does exactly what `uss::htmlentities` do!
		
		Except that PHP default functions does not convert apostrophe!
		
		Which I don't consider sufficient...
		
	*/
	
	public static function htmlentities( ?string $string = null ) {
		$string = htmlspecialchars( $string );
		$string = str_replace("'", "&apos;", $string);
		return $string;
	}
	
	
	/*
	
		- Create a One-Time Security Key
		
		====================================
		
		
		Well! Who says a website doesn't need strict security? 
		
		The `uss::nonce()` method create a one-time SHA256 character based on a secret input + uss_session_id
		
		
		For Example: 
			
			`uss::nonce( "Your secret input here" )` // returns a token
			
			
		This will return a unique token which you can always test on the server side:
		
			$_POST['token'] === `uss::nonce( "Your secret input here" );
			
			
		This can be helpful to minimize spam and hacks
		
	*/
	
	public static function nonce( $input = '1' ) {
		
		// generate a new session_id;
		
		$hash = call_user_func(function() use($input) {
			
			// get length of uss_session_id
			$length = strlen($_SESSION['uss_session_id']);
			
			// join hashed input with hashed session_id;
			$bind_hash = hash('sha256', session_id()) . hash('sha256', $input);
			
			// extract some string and split the string into array;
			$input =  str_split( substr($bind_hash , -$length), 5 );
			
			// encode the uss_session_id and split the string into array;
			$session_id = str_split( str_rot13( $_SESSION['uss_session_id'] ), 5 );
			
			$result = [];
			
			// now! rearrange the strings into a very improper and abnormal way
			for( $x = 0; $x < count($session_id); $x++ ) {
				$__a = str_rot13( $input[ $x ] ?? '' );
				$__b = str_rot13( $session_id[ $x ] ?? '' );
				$result[] = $__a . $__b;
			};
			
			// join the improper string
			return implode('', $result);
			
		});
		
		// return a hashed version the improper string!
		
		return hash('sha256', $hash);
		
	}
	
	
	/*
	
		I Love JSO... 
		I mean, uss platform works great with JSON!
		
		`uss::stop()` method is the platform way of calling `die()` or `exit()`
		
		It exits the script and print a json response
		
		parameter 1: The status response
		parameter 2: A message for the client
		parameter 3: Data used for making addition changes based on the response status
	
	*/
	
	public static function stop( ?bool $status, ?string $message = null, ?array $data = [] ) {
		$data = array(
			"status" => (boolean)$status,
			"message" => $message,
			"data" => $data
		);
		$json = json_encode($data);
		exit( $json );
	}
	
	
	/*
		@Save a variable in javascript
		--------------------------------
		
		One common relevant activity in PHP is passing some information to the client browser for usage
		
		The `uss::console()` method makes this Mission Impos... sorry! makes this Mission Possible
		
		The `uss::console()` method accepts 1 or 2 argument
		
			If ( argument 1 is `NULL` ): It returns a list of all consoled index
			
			else If ( argument 1 is `STRING` && argument 2 is NOT DEFINED AT ALL ): it returns the value of the indexed string
			
			else: It saves the value into the console
			
		
		ACCESSING SAMPLE:
		-----------------
		
			ON PHP:
				
				uss::console( 'my_var', "The value" );
				
			ON JAVASCRIPT:
			
				uss.my_var // "The Value";
				
				uss['my_var'] // "The Value";
				
				
		WARNING:
		--------
			
			DO NOT SAVE SENSITIVE INFORMATION INTO THE CONSOLE!
			IT CAN EASILY BE ACCESS IN THE CLIENT BROWSER AS THAT IS SOLELY WHAT IT'S MADE FOR
	*/
	
	public static function console( ?string $key = null ) {
		// accepts 2 arguments
		if( is_null($key) ) return self::$console;
		$key = trim($key);
		$args = func_get_args();
		if( count($args) === 1 ) return self::$console[ $key ] ?? null;
		self::$console[ $key ] = $args[1];
	}
	
	
	/*
		
		Remove a javascript dedicated variable 
		
		ON PHP:
		
			uss::remove_console( 'my_var' );
			
		ON JAVASCRIPT:
		
			uss.my_var // undefined
		
	*/
	
	public static function remove_console( string $key ) {
		if( isset(self::$console[$key]) ) unset( self::$console[ $key ] );
	}
	
};

/*
	GREAT! WE'VE DONE A LOT IN THE USS CLASS
	NOW! LET'S INITIALIZE IT!
*/

uss::__init();