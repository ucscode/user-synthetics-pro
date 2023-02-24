<?php 

class core {

	/*
		--- [ CONVERT BACKSLASH TO FORWARD SLASH ]
	*/
	
	public static function rslash( $PATH ) {
		return str_replace("\\", "/", $PATH);
	}
	
	
	/* 
		--- [ CONVERT SERVER PATH TO URL ] 
	*/
	
	public static function url( $ABS_PATH, $MINI = FALSE ) {
		$ABS_PATH = self::rslash( $ABS_PATH );
		$SRV_URL = preg_replace( "~^{$_SERVER['DOCUMENT_ROOT']}~i", $_SERVER['SERVER_NAME'], $ABS_PATH );
		$SCHEME = ($_SERVER['REQUEST_SCHEME'] ?? ($_SERVER['SERVER_PORT'] == '80' ? 'http' : 'https'));
		return (!$MINI ? ($SCHEME . "://") : '/') . $SRV_URL;
	}
	
	
	
	/* 
		--- [ ARRAY TO ATTR ] 
	*/
	
	public static function array_to_html_attrs( array $array, bool $apos = false ) {
		return implode(" ", array_map(function($key, $value) use($apos) {
			if( is_array($value) ) $value = json_encode($value);
			$value = htmlspecialchars( $value );
			if( $apos ) {
				$apos = "'";
				$value = str_replace("'", "&apos;", $value);
			} else $apos = '"';
			return "{$key}={$apos}{$value}{$apos}";
		}, array_keys($array), array_values($array)));
	}
	
	
	/* 
		--- [ GENERATE KEY ]
	*/
	
	public static function keygen($length = 10, bool $use_spec_char = false) {
		$data = range(0, 9); 
		foreach( [range('a', 'z'), range('A', 'Z')] as $array ) {
			foreach( $array as $value ) {
				$data[] = $value;
			};
		};
		if( $use_spec_char ) {
			$special = ['!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '[', ']', '{', '}', '/', ':', '.', ';', '|', '>', '~', '_', '-'];
			foreach( $special as $char ) $data[] = $char;
		};
		$key = '';
		for( $x = 0; $x < $length; $x++ ) {
			shuffle($data);
			$key .= $data[0];
		};
		return $key;
	}
	
	
	/* 
		--- [ REPLACE %{var} WITH ARRAY VALUE ] 
	*/
	
	public static function replace_var( string $string, array $data ) {
		$new_string = preg_replace_callback( "~%\{([^\}]+)\}~", function( $match ) use($data) {
			$key = $match[1];
			return $data[ $key ] ?? $match[0];
		}, $string );
		return $new_string;
	}
	
	
	/* 
		--- [ GET STRONG REGULAR EXPRESSION ]
	*/
	
	public static function regex( string $name, $strict = false ) {
		if( $strict )  {
			$BEGIN = '^';
			$END = '$';
		} else $BEGIN = $END = NULL;
		## ----- Create REGEX ------
		switch( strtoupper($name) ) {
			case 'EMAIL':	
				return '/' . $BEGIN . '(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))' . $END . '/';
			case "URL":
				return "/{$BEGIN}(?:https?:\/\/)?(?:[\w.-]+(?:(?:\.[\w\.-]+)+)|(?:localhost(:\d{1,4})?\/))[\w\-\._~:\/?#[\]@!\$&'\(\)\*\+,;=.%]+{$END}/i";
			case "NUMBER":
				return "/{$BEGIN}\-?\d+(?:\.\d+)?{$END}/";
			case "DATE":
				return "/{$BEGIN}(0[1-9]|[1-2][0-9]|3[0-1])(?:\-|\/)(0[1-9]|1[0-2])(?:\-|\/)[0-9]{4}{$END}/i";
			case "BTC":
				$regex = "/{$BEGIN}[13][a-km-zA-HJ-NP-Z0-9]{26,33}{$END}/i";
				break;
		}
	}

	
	/*
		--- [ CHECK IF PHP NAMESPACE EXISTS ]
	*/
	
	public static function namespace_exists($namespace) {
		// credit to stackoverflow
		$namespace .= '\\';
		foreach( get_declared_classes() as $classname )
			if( strpos($classname, $namespace) === 0 ) return true;
		return false;
	}
	
	
	/*
		--- [ SANITIZE INPUT OR ARRAY ]
	*/
	
	public static function sanitize( $content, $func = 'htmlspecialchars' ) {
		$class = __CLASS__;
		$method = __FUNCTION__;
		if( !is_callable($func) ) throw new Exception( "Argument provided in parameter 2 of {$class}::{$method}() is not callable" );
		if( is_array($content) || is_object($content) ) {
			foreach( $content as $key => $value )
				$content[ $key ] = self::sanitize( $value, $func );
		} else $content = call_user_func($func, $content);
		return $content;
	}
	
	
	/*
		--- [ SOME MOMENT AGO ] ---
	*/
	
	public static function elapse( $DateTime, ?bool $full = null ) {
		 
		$Now = new DateTime("now");
		
		if( $DateTime instanceof DateTime ) {
			// Object;
			$Time = $DateTime;
		} else if( !is_numeric($DateTime) ) {
			// Timestamp String
			$Time = new DateTime( $DateTime );
		} else {
			// Unix Timestamp;
			$Time = (new DateTime("now"))->setTimestamp($DateTime);
		}
		
		$diff = $Now->diff( $Time );
		
		$diff->w = floor( $diff->d / 7 );
		$diff->d -= $diff->w * 7;
		
		$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second'
		);
		
		foreach( $string as $k => &$v ) {
			if($diff->$k)
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			else
				unset($string[$k]);
		};
		
		if( !$full ) {
			$string = array_slice( $string, 0, 1 );
			if( $full === false && $string ) {
				$string = array_values($string);
				preg_match("/\d+\s\w/i", $string[0], $match);
				return str_replace(" ", '', $match[0]);
			}
		}
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}
	
	
	/*
		Note:	This function belown is not certain to detect robot!
				A change in user agent may not correctly detect bots 
	*/
	
	public static function robot() {
		
		if( isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT']) ) {
			
			$bot_regex = '/abacho|accona|AddThis|AdsBot|ahoy|AhrefsBot|AISearchBot|alexa|altavista|anthill|appie|applebot|arale|araneo|AraybOt|ariadne|arks|aspseek|ATN_Worldwide|Atomz|baiduspider|baidu|bbot|bingbot|bing|Bjaaland|BlackWidow|BotLink|bot|boxseabot|bspider|calif|CCBot|ChinaClaw|christcrawler|CMC\/0\.01|combine|confuzzledbot|contaxe|CoolBot|cosmos|crawler|crawlpaper|crawl|curl|cusco|cyberspyder|cydralspider|dataprovider|digger|DIIbot|DotBot|downloadexpress|DragonBot|DuckDuckBot|dwcp|EasouSpider|ebiness|ecollector|elfinbot|esculapio|ESI|esther|eStyle|Ezooms|facebookexternalhit|facebook|facebot|fastcrawler|FatBot|FDSE|FELIX IDE|fetch|fido|find|Firefly|fouineur|Freecrawl|froogle|gammaSpider|gazz|gcreep|geona|Getterrobo-Plus|get|girafabot|golem|googlebot|\-google|grabber|GrabNet|griffon|Gromit|gulliver|gulper|hambot|havIndex|hotwired|htdig|HTTrack|ia_archiver|iajabot|IDBot|Informant|InfoSeek|InfoSpiders|INGRID\/0\.1|inktomi|inspectorwww|Internet Cruiser Robot|irobot|Iron33|JBot|jcrawler|Jeeves|jobo|KDD\-Explorer|KIT\-Fireball|ko_yappo_robot|label\-grabber|larbin|legs|libwww-perl|linkedin|Linkidator|linkwalker|Lockon|logo_gif_crawler|Lycos|m2e|majesticsEO|marvin|mattie|mediafox|mediapartners|MerzScope|MindCrawler|MJ12bot|mod_pagespeed|moget|Motor|msnbot|muncher|muninn|MuscatFerret|MwdSearch|NationalDirectory|naverbot|NEC\-MeshExplorer|NetcraftSurveyAgent|NetScoop|NetSeer|newscan\-online|nil|none|Nutch|ObjectsSearch|Occam|openstat.ru\/Bot|packrat|pageboy|ParaSite|patric|pegasus|perlcrawler|phpdig|piltdownman|Pimptrain|pingdom|pinterest|pjspider|PlumtreeWebAccessor|PortalBSpider|psbot|rambler|Raven|RHCS|RixBot|roadrunner|Robbie|robi|RoboCrawl|robofox|Scooter|Scrubby|Search\-AU|searchprocess|search|SemrushBot|Senrigan|seznambot|Shagseeker|sharp\-info\-agent|sift|SimBot|Site Valet|SiteSucker|skymob|SLCrawler\/2\.0|slurp|snooper|solbot|speedy|spider_monkey|SpiderBot\/1\.0|spiderline|spider|suke|tach_bw|TechBOT|TechnoratiSnoop|templeton|teoma|titin|topiclink|twitterbot|twitter|UdmSearch|Ukonline|UnwindFetchor|URL_Spider_SQL|urlck|urlresolver|Valkyrie libwww\-perl|verticrawl|Victoria|void\-bot|Voyager|VWbot_K|wapspider|WebBandit\/1\.0|webcatcher|WebCopier|WebFindBot|WebLeacher|WebMechanic|WebMoose|webquest|webreaper|webspider|webs|WebWalker|WebZip|wget|whowhere|winona|wlm|WOLP|woriobot|WWWC|XGET|xing|yahoo|YandexBot|YandexMobileBot|yandex|yeti|Zeus/i';
			
			return !!preg_match( $bot_regex, $_SERVER['HTTP_USER_AGENT'] );
			
		} else return true;
		
	}
	
}