<?php 

/*
	Author: UCSCODE
	Profile: https://github.com/ucscode
	URL: https://ucscode.me
	Date: 18th Jan 2022 +1 GMT
	Project: Menufy v2.0
*/

class menufy {
		
	protected $name;
	protected $parent_menu = null;
	protected $level = null;
	protected $child = [];
	protected $attrs = [];
	
	public function __construct( ?string $name = null, array $attrs = [], ?menufy $menufy = null ) {
		$this->name = $name ?? __CLASS__;
		foreach( $attrs as $key => $value ) $this->set_attr($key, $value);
		## Get the parent menu;
		if( $menufy ) {
			$this->level = is_null($menufy->level) ? 0 : $menufy->level + 1;
			$this->parent_menu = $menufy;
		}
	}
	
	## --------------- [ MANAGE MENU ] -----------------
	
	public function add( string $name, array $attrs = [] ) {
		$_class = __CLASS__;
		if( !empty($this->child[ $name ]) ) throw new Exception( "($_class) Duplicate Child: {`{$name}`} already added to {`{$this->name}`}" );
		$this->child[ $name ] = new self( $name, $attrs, $this );
		return $this->child[ $name ];
	}
	
	public function get( string $name ) {
		return $this->child[ $name ] ?? null;
	}
	
	public function remove( string $name ) {
		if( isset($this->child[ $name ]) ) unset($this->child[ $name ]);
		return $this;
	}
	
	## ---------------- [ MANAGE ATTRS ] ----------------
	
	public function set_attr( string $name, $value ) {
		$this->attrs[ $name ] = $value;
		return isset($this->attrs[ $name ]);
	}
	
	public function get_attr( string $name ) {
		return $this->attrs[ $name ] ?? null;
	}
	
	public function remove_attr( string $name ) {
		if( isset($this->attrs[ $name ]) ) unset($this->attrs[ $name ]);
		return !isset($this->attrs[ $name ]);
	}
	
	## ----------------- [ CONTROL USAGE ] -----------------
	
	public function __set($key, $value) {
		$_class = __CLASS__;
		throw new Exception( "Do not set `\${$_class}::{$key}` value directly. Use the {$_class}::set_attr() method" );
	}
	
	public function __get($key) {
		return $this->{$key} ?? null;
	}
	
	public function __isset($var) {
		return !empty($this->{$var});
	}
	
	## ---------------- [ LOOP MENU ] ------------------
	
	public function iterate( callable $func ) {
		foreach( $this->child as $menufy ) $func( $menufy, $func );
	}
	
}



/*

	menufy::() usage sample;
	------------------------
	
	echo "<ul class='main-nav'>";

	$menufy->iterate(function($menu, $closure) {
		
		echo "
			<li class='nav-item'>
				<a href='{$menu->get_attr('href')}'> {$menu->get_attr('label')} </a>
		";
			
			if( !empty($menu->child) ) {
				
				echo "<ul class='sub-menu'>";  
				
					$menu->iterate( $closure ); 
					
				echo "</ul>";
				
			};
		
		echo "</li>";
		
	});

	echo "</ul>";
	
*/