<?php

/**
	* Library Name: Events
	* Author: UCSCODE
	* Author Name: Uchenna Ajah
	* Author URI: https://ucscode.me
	* Github URI: https://github.com/ucscode
	* Description: 	This amazing class allows you to declear functions and execute it at a certain position 
					without overriding or disrupting a previously declared function.	
**/

class events {

	protected static $events = array();
	
	public static function exec( string $eventType, array $eventdata = array(), ?bool $sort = true, ?callable $master = null ) {
		
		// get the event type to execute;
		
		$eventType = trim($eventType);
		
		if( !array_key_exists($eventType, self::$events) ) return;
		
		$eventList = self::$events[ $eventType ];
		
		/*	
			sort events by key;
			--------------------
			null == no sorting
			true == sort ascending
			false ==  sort descending
		*/
		
		if( $sort ) ksort($eventList);
		else if( !is_null($sort) ) krsort($eventList);
		
		// execute events;
		
		foreach( $eventList as $key => $action ) {
			
			/*
				The `$master` is a callable that is used to track or enforce conditions on other events;
				it can be used to determine which event is running and set conditions for the event to run!
				The master event must return a (boolean)false to cancel an event;
				Else, The event will run;
			*/
			
			$exec = is_null($master);
			if( !$exec ) $exec = ($master($key, $action['file']));
			
			if( $exec !== false ) $action['callable']( $eventdata, $eventType );
			
		};
		
	}
	
	// ----- [{ add event listener }] -----
	
	public static function addListener(string $eventTypes, callable $function, ?string $uid = null) {
		$debug = debug_backtrace()[0];
		# UID = Unique ID
		self::splitEvents($eventTypes, function($event) use($function, $uid, $debug) {
			if( !array_key_exists($event, self::$events) ) self::$events[ $event ] = array();
			$eventList = &self::$events[ $event ];
			$action = array(
				"file" => $debug['file'],
				"callable" => $function
			);
			if( is_null($uid) ) $eventList[] = $action;
			else if( !array_key_exists($uid, $eventList) ) $eventList[ $uid ] = $action; 
		});
	}
	
	// ----- [{ remove event listener by id }] -----
	
	public static function removeListener(string $eventTypes, ?string $uid = null) {
		self::splitEvents($eventTypes, function($event) use($uid) {
			if( !array_key_exists($event, self::$events) ) return;
			$eventList = &self::$events[ $event ];
			if( is_null($uid) && !empty($eventList) ) array_pop($eventList);
			else if( array_key_exists($uid, $eventList) ) unset($eventList[ $uid ]);
			// Finally: trash empty event;
			if( empty($eventList) ) self::clear($event);
		});
	}
	
	// ----- [ clear all relative event listeners ] -----
	
	public static function clear(string $eventTypes) {
		self::splitEvents($eventTypes, function($event) {
			if( array_key_exists($event, self::$events) ) unset(self::$events[ $event ]);
		});
	}
	
	// ----- [{ get a an event by id }] -----
	
	public static function getListener(string $eventType, ?string $uid = null) {
		$eventType = trim($eventType);
		foreach( self::$events as $key => $eventList ) {
			if( $key == $eventType ) {
				if( is_null($uid) ) {
					$eventKeys = array_keys($eventList);
					$lastIndex = end($eventKeys);
					return $eventList[ $lastIndex ]['callable'];
				} else if( array_key_exists($uid, $eventList) ) {
					return $eventList[$uid]['callable'];
				}
			};
		};
	}
	
	// ----- [ check if event exists ] -----
	
	public static function hasListener(string $eventType, ?string $uid = null) {
		return !!self::getListener($eventType, $uid);
	}
	
	// ----- [ clear all relative event listeners ] -----
	
	private static function splitEvents(string $eventTypes, callable $func) {
		$eventTypes = array_map("trim", explode(",", $eventTypes));
		foreach( $eventTypes as $eventType ) $func( $eventType );
	}
	
	public static function list( $priority = false ) {
		return !$priority ? array_keys(self::$events) : self::$events;
	}

};

