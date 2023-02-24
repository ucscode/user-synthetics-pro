<?php 

/*

	Project Name: X2Client
	
	Description: Convert HTML 5 related syntax into Table format supported by all email clients
	
	Difficulty: Easy to use
	
	Version: 1.1
	
	Production Date: 17th Feburary 2023
	
	Author: UCSCODE
	
	Author Website: https://ucscode.me
	
	Author Profile: https://github.com/ucscode
	
*/

class X2Client {
	
	protected $namespace = "x2";
	protected $domain = 'https://ucscode.me/x2client';
	protected $dom;
	protected $hashTag;
	protected $errors;
	protected $cssRules;
	protected $XML;
	
	protected $block = array(
		"div",
		"p"
	);
	
	public function __construct( $syntax ) {
		
		// Prevent Output Of XML Error;
		
		libxml_use_internal_errors(true);
		
		# I'M NOT PERFECT! BUT I'LL TRY MY BEST TO HANDLE SOME BASIC XML ERRORS:
		# LET'S DO THIS
		
		/* 
			ERROR 1: EntityRef: expecting ';'
			
			SOLUTION: Replace & with &amp; only if it is not a valid HTML Entity;
		*/
		
		$syntax = preg_replace("/&(?!([\w\n]{2,7}|#[\d]{1,4});)/","&amp;", $syntax);
		
		/*
			ERROR 2: Opening and ending tag mismatch: ...
			
			SOLUTION: Replace self-closing tags such as <br> with <br/>
		*/
		
		$syntax = $this->handleMismatchedTags( $syntax );
		
		/*
			ERROR 3: Entity 'x' not defined
			Where x can be nbsp, mdash etc;
			
			These entities are valid for HTML but not valid for XML.
			So! No fix available :(
			
			Wait!!! According to my research in:
			
			https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
			
			The only character entities that have an "actual name" defined (instead of using a numeric reference) are:
			
			- &amp; 
			- &lt; 
			- &gt; 
			- &quot
			- &apos;
			
			That means you have to use the numeric equivalent...
			
			---------------------------------------------------------
			
			SOLUTION: Let's get a list of all HTML entities and convert them to their numeric equivalence;
			
			Thanks to - https://github.com/w3c/html/blob/master/entities.json
			
		*/
		
		$entities = json_decode( file_get_contents( __DIR__ . "/entities.json" ), true );
		
		$syntax = preg_replace_callback( "/" . implode("|", array_keys($entities)) . "/i", function($match) use($entities) {
			$key = $match[0];
			$value = '&#' . $entities[$key]['codepoints'][0] . ";";
			return $value;
		}, $syntax );
		
		/*
			ERROR 4: Error parsing attribute name
			
			I discovered this error when a CSS Comment was found on a style tag.
			
			/* ERROR CAUSE! * /
			
			SOLUTION: Remove CSS Comment Tags
		*/
		
		$expression = "~\/\*(.*?)(?=\*\/)\*\/~s";
		
		$syntax = preg_replace( $expression, '', $syntax );
		
		/*
			
			Base on research, another problem came from using attribute value with a name
			
			<cardId ="01"> instead of <card Id="01">
			
			SOLUTION: NONE!
			
			WHY? 
				- Because We can't tell whether 'cardId' is tag on it's own
				- We also cannot tell whether the separation should be <car dId="01"> or <cardI d="01">
			
			It's left for the developer to monitor the syntax and correct such error!
			
		*/
		
		/*
			I'll try fixing more errors when I find them!
			Let Proceed...
		*/
		
		/* 
			Now! Let's create a random string tag that we can use as the root element
			On this root element, we declare our namespace
		*/
		
		$this->hashTag = "_" . sha1( mt_rand() );
		
		$xml = "
			<{$this->namespace}:{$this->hashTag} xmlns:{$this->namespace}='{$this->domain}'>
				{$syntax}
			</{$this->namespace}:{$this->hashTag}>
		";
		
		/*
			Now! Let's Create DOMDocument and load the XML String;
		*/
		
		$this->dom = new DOMDocument( "1.0", "utf-8" );
		
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput = true;

		$this->XML = trim($xml);
		
		$this->dom->loadXML( $this->XML );
		
		/*
			Now! Let's get ready to make some advance search using DOMXPath;
			Since DOMDocument doesn't use css ;)
		*/
		
		$this->xpath = new DOMXPath( $this->dom );
		$this->xpath->registerNamespace( $this->{"namespace"}, $this->domain );
		
		/*
			Now Let's import a CSS to XPath Translator;
			
			I found this online and I love it because of it's simplicity
			
			https://github.com/PhpGt/CssXPath
		*/
		require_once __DIR__ . "/Translator.php";
		
		/*
			USAGE: 
			
			$xPath = new Gt\CssXPath\Translator( "css selector" );
			DOMXPath::Query( $xPath );
			
			Very simple!
			
			Now! Let's store the error.
			
			So Incase the XML Doesn't Load, We can easily check what's causing the problem;
			
		*/
		
		$this->errors = libxml_get_errors();
		
		/*
			After the 
				
				H2Client::__construct( $XML_STRING )
				
			The next thing is to render 
			
				H2Client::render();
			
			
		*/
		
	}
	
	public function __get($name) {
		return $this->{$name} ?? null;
	}
	
	protected function handleMismatchedTags( $syntax ) {
		/*
		
			With the method, we try to close self-closing tags that are not closed!
			such as using <br> instead of <br />
			
			Or better said:
			using <x2:br> instead of <x2:br />
			
		*/
		$tags = array(
			"area", 
			"base", 
			"br", 
			"col", 
			"embed", 
			"hr", 
			"img", 
			"input", 
			"link", 
			"meta", 
			"param", 
			"source", 
			"track", 
			"wbr"
		);
		foreach( $tags as $tagname ) {
			$expression = "~<((?:{$tagname}|{$this->namespace}:{$tagname})[^>]*)~";
			$syntax = preg_replace_callback( $expression , function($matches) {
				$tag = $matches[0];
				if( substr($tag, -1) != "/" ) $tag .= "/";
				return $tag;
			}, $syntax );
		};
		return $syntax;
	}
	
	protected function transformNode( $element ) {
		
		/*
		
			This is where we convert namespace node into regular node such as
			
			<x2:a href=''> into <a href=''>
			
			This is also where we convert <x2:div /> or <x2:p />
			
			into <table /> or <td /> 
			
		*/
		
		if( !$element ) return;
		
		/*
			Let's search for the children of the element that should be transformed
			
			However, we cannot use a foreach loop!
			
			But Why :-/ ?
			
			Because we are going to be replacing the child nodes with lots of <table/>, </tr> and other regular non-namespace element.
			
			Since nodes are passed by reference, then, when we remove an element by replacing it with another one, 
			the element no longer exists in the node list, and the next one in line takes its position in the index. 
			Then when foreach hits the next iteration, and hence the next index, one will be effectively skipped
			
			And we definitely don't wanna skip any node!
			
			SOLUTION: Use for loop. :-)
			
		*/
		
		for( $x = 0; $x < $element->childNodes->length; $x++ ) {
			
			// Get the childNode;
			
			$node = $element->childNodes->item($x);
			
			/*
				Unfortunately, there are different kinds of node!
				But we want only element Nodes
			*/
			
			if( !$this->isElement( $node ) ) continue;
			
			/*
				Now let's get the original tagName;
				converting - x2:div into div
			*/
			
			$tag = $this->HTMLTag( $node->nodeName );
			
			/*
				If the tag is a block element such as DIV | P
				We convert it into a table.
				Otherwise, we replace it with an equivalent element that doesn't have namespace
			*/
			
			if( in_array($tag, $this->block) ) {
				$node = $this->convert2Tr( $node );
			} else $node = $this->renameNode( $node, $tag );
			
			/*
				If the node has child Elements!
				Then that means we're not done yet.
				We just have to repeat the process again
			*/
			
			if( $node->childNodes->length ) $this->transformNode( $node );
			
		};
		
	}
	
	protected function HTMLTag( $nodeName ) {
		/*
			Get the original element name!
			We achieve this by removing the namespace from the tagName
		*/
		return str_replace( $this->{"namespace"} . ":", '', $nodeName );
	}
	
	protected function groupTr( $element ) {
		
		/*
			We'll search for all the <tr/> element that has no <table/> parent
		*/
		
		$xpath = (string)(new Gt\CssXPath\Translator("tr"));
		$nodes =  $this->xpath->query( $xpath );
		
		for( $x = 0; $x < $nodes->length; $x++ ) {
			
			$tr = $nodes->item($x); // The <tr /> element
			
			//Check if the parent is not a <table /> element
			
			if( $tr->parentNode->nodeName != 'table' ) {
				
				// Get the parent Node;
				
				$parentNode = $tr->parentNode; 
				
				// Create a Table Node So we can append the tr to it;
				
				$table = $this->dom->createElement('table');
				
				/*
					Now we have to get a list of all <tr /> that should be appended to the table
					Otherwise, a table will be forcefully and unwillingly created for each row irrespectively
				*/
				
				$tr_list = array();
				foreach( $tr->parentNode->childNodes as $childTr ) $tr_list[] = $childTr ;
				
				// Insert the table before the existence of the first table rows
				
				$parentNode->insertBefore( $table, $tr_list[0] );
				
				/* Now! Append all the child table rows to it */
					
				foreach( $tr_list as $childTr ) {
					
					/*
						Let's handle some few error!
						Shall We?
					*/
					
					if( $this->isEmpty( $childTr ) ) continue;
					
					/*
						Only <tr /> can be a child of <table />
						
							( :-O I am not your browser so stop asking me about <tbody /> )
							
						Now back to business... I mean coding!
					*/
					
					if( $childTr->nodeName != 'tr' ) {
						$tr = $this->dom->createElement( 'tr' );
						$td = $this->dom->createElement( 'td' );
						$tr->appendChild( $td );
						$td->appendChild( $childTr );
						$childTr = $tr;
					};
					
					$table->appendChild( $childTr );
					
				}
				
				/* 
					Now we have to style and add the necessary attributes that most Email Client will require
				*/
				
				$this->styleTable( $table );
				
			};
			
		}
		
	}
	
	protected function convert2Tr( $node ) {
		
		// Create Table Element;
		
		$tr = $this->dom->createElement('tr');
		$td = $this->renameNode( $node, 'td' );
		$td->parentNode->insertBefore( $tr, $td );
		$tr->appendChild( $td );
		$this->styleTd( $td, $node );
		
		return $tr;
		
	}
	
	protected function styleTable( $table ) {
		
		/*
		
			This table attribute advice was from MailMunch
			
			You have a better one? Let us know now!
			
		*/
		
		$attributes = array(
			"width" => '100%',
			"align" => 'left',
			"border" => 0,
			"cellspacing" => 0,
			"cellpadding" => 0,
			"style" => "max-width: 100%; table-layout: fixed; word-break: break-word;"
		);
		
		foreach( $attributes as $name => $value ) {
			$table->setAttribute( $name, $value );
		}
		
	}
	
	protected function styleTd( $td, $node ) {
		
		/*
			Right here, we inherit the style of the element
			By passing it to the <td />
		*/
		
		if( !$this->isElement($node) ) return;
		
		$attributes = array();
		
		foreach( $attributes as $name => $value ) {
			$td->setAttribute( $name, $value );
		};
		
		foreach( $node->attributes as $attr ) {
			if( $attr->name == 'style' ) {
				if( !in_array( $this->HTMLTag( $node->nodeName ), $this->block ) ) continue;
			} else if( in_array( $attr->name, ['href', 'src'] ) ) continue;
			$td->setAttribute( $attr->name, $attr->value );
		}
		
		$this->setMarker( $td, $node );
		
	}
	
	protected function isEmpty( $node ) {
		if( $node->nodeType == 3 ) {
			$nodeValue = trim( $node->nodeValue );
			return empty( $nodeValue );
		};
		return false;
	}
	
	protected function isElement( $node ) {
		return $node->nodeType === 1;
	}
	
	protected function setMarker( $el, $node ) {
		
		/*
			Seriously, writing this script was so confusing!
			The script was tested with a complete HTML page loaded with syntax
			
			Anyway! The marker helps us leave a trace so we can use to know which element was converted to 
			<table /> or <td /> element.
			
			As well as identity the element parents after the conversion!
			
		*/
		
		if( !$this->isElement($node) ) return;
		
		$el->setAttribute( "data-marker", $this->HTMLTag( $node->nodeName ) );
		
		$markers = array(
			'id' => '#',
			'class' => '.'
		);
		
		foreach( $markers as $attr => $selector ) {
			$value = trim( $node->getAttribute( $attr ) );
			if( !empty($value) ) {
				$marker = $el->getAttribute( "data-marker" );
				if( $attr == 'id' ) $marker .= "{$selector}{$value}";
				else {
					$marked = implode('.', array_map('trim', explode(" ", $value)));
					$marker .= ".{$marked}";
				};
				$el->setAttribute( "data-marker", $marker );
			};
		};
		
	}
	
	protected function renameNode( $node, $tag ) {
		
		/*
		
			DOMDocument Node cannot be renamed.
			Therefore, we need to create a new element, assign the new name to it and replace the old element
			
			If you ask me once more why we use for loop to transformNode, I'll punch your face!
			
		*/
		
		$newNode = $this->dom->createElement( $tag );
		
		// preserve attributes;
		foreach( $node->attributes as $attr )
			$newNode->setAttribute( $attr->name, $attr->value );
			
		// preserve children
		foreach( $node->childNodes as $childNode ) 
			$newNode->appendChild( $childNode->cloneNode(TRUE) );
		
		$node->parentNode->replaceChild( $newNode, $node );
		
		return $newNode;
		
	}
	
	public function render( ?string $css_selector = null ) {
		
		/*
		
			The only public method availabe in this library after the __constructor();
			It either gives you the result you need or false
			
			If it returns false, then you should consider using X2Client::$errors to check for errors
			
		*/
		
		if( empty($this->errors) ) {
			
			// get the root element;
			
			$element = $this->dom->getElementsByTagNameNS( $this->domain, $this->hashTag )->item(0);
			
			/*
				Now! Convert Internal CSS into Inline CSS
				Unless you want email clients to stripe your <style /> tag and make your email look like shit!
			*/
			
			$this->captureCss( $element );
			
			/*
				This is the first instance where we call the transformNode;
				Inside it, a recursion occurs until all nodes are completely transformed
			*/
			
			$this->transformNode( $element );
			
			/*
				Now there are bunch of <tr/><td/> everywhere
				None wrapped within a table.
				Now! Let's group each tr based on parent Element
			*/
			
			$this->groupTr( $element );
			
			/*
				Let's get the final result!
			*/
			
			$result = '';
			
			$nodeParent = $this->renderMode( $element, $css_selector );
			
			if( $nodeParent === $element ) {
				
				foreach( $nodeParent->childNodes as $node ) 
					$result .= $this->dom->saveXML( $node ) . "\n";
					
			} else $result = $this->dom->saveXML( $nodeParent );
			
			/*
				Hurray! We made it!
			*/
			
			return $result;
			
		} else return false;
		
	}
	
	protected function renderMode( $element, $css_selector ) {
		
		if( !empty($css_selector) ) {
			
			$css_selector = preg_replace( "/{$this->namespace}:/i", '', trim($css_selector) );
			
			$xquery = (string)(new Gt\CssXPath\Translator( $css_selector ));
			
			$node = $this->xpath->query( $xquery )->item( 0 );
			
			if( $node ) $element = $node;
			
		};
		
		return $element;
		
	}
	
	protected function captureCss( $node ) {
		
		$rules = array();
		
		/*
		
			I'd like you to know that your style tag must also start with the x2: namespace
			
			<x2:style> Your style here </x2:style>
			
		*/
		
		// get all available style tags;
		
		$styles = $this->xpath->query( ".//{$this->namespace}:style", $node );
		
		// Make them inline;
		
		foreach( $styles as $style ) {
			$style->nodeValue = str_replace( "{$this->namespace}:", '', $style->nodeValue );
			$this->parse_css( $style->nodeValue, $rules );
		};
		
		// return the rules as an array;
		
		return $rules;
		
	}
	
	protected function parse_css( string $css, &$css_array ) {
		
		/*
			This css parser, created by me :] works like magic;
		*/
		
		$elements = explode('}', $css);
		
		foreach ($elements as $element) {
			
			$rule_break = array_filter( array_map('trim', explode('{', $element) ) );
			
			if( count($rule_break) < 2 ) continue;
			
			// get the name of the CSS element
			
			$name = trim($rule_break[0]);
			$name = preg_replace( "/\s+/", ' ', $name );
			$name = preg_replace( "/{$this->namespace}:/i", '', $name );
			
			if( substr($name, 0,1) == '@' ) continue;
			
			$xPath = (string)(new Gt\CssXPath\Translator( $name ));
			$xPath = preg_replace( "/\/\/(\w+)/i", "//{$this->namespace}:$1", $xPath );
			
			// get all the key:value pair styles
			$rules = array_filter( array_map('trim', explode(';', $rule_break[1])) );
			
			$container = array();
			
			// remove element name from first property element
			foreach( $rules as $rule ) {
				$style_break = array_map('trim', explode( ":", $rule ));
				$container[ $style_break[0] ] = $style_break[1];
			};
			
			if( array_key_exists($name, $css_array) ) {
				$css_array[ $name ] = array_merge( $css_array[ $name ], $container );
			} $css_array[ $name ] = $container;
			
			// convert the internal css into inline css;
			
			$this->injectInlineCss( $this->xpath->query( $xPath ), $container );
			
		}
		
		return $css_array;
		
	}
	
	protected function injectInlineCss( $nodes, $style ) {
		
		/*
			Convert the style from an array to a string
		*/
		
		$inlineStyle = [];
		
		foreach( $style as $key => $value ) {
			$inlineStyle[] = "{$key}: $value";
		};
		
		$inlineStyle = implode( "; ", $inlineStyle );
		
		// Now push the string into the node;
		
		foreach( $nodes as $node ) {
			$node->setAttribute( 'style', $inlineStyle );
		}
		
	}
	
}