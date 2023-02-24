<?php 

// Created By UCSCODE;

class pairs {
	
	private $tablename;
	private $mysqli;
	
	public function __construct(MYSQLI $mysqli, string $tablename, $auto_create = true) {
		if( !class_exists('sQuery') ) 
			throw new Exception( "pairs::__construct() relies on class `sQuery` to operate" );
		$this->tablename = $tablename;
		$this->mysqli = $mysqli;
		if( $auto_create ) $this->createTable();
	}
	
	public function createTable() {
		$SQL = "
			CREATE TABLE IF NOT EXISTS `{$this->tablename}` (
				`id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
				`_ref` INT,
				`_key` VARCHAR(255) NOT NULL,
				`_value` TEXT,
				`epoch` BIGINT NOT NULL DEFAULT UNIX_TIMESTAMP()
			);
		";
		$this->mysqli->query( $SQL );
	}
	
	private function test( ?int $ref = null ) {
		if( is_null($ref) ) $test = " IS " . sQuery::val( $ref );
		else $test = " = " . sQuery::val( $ref );
		return trim($test);
	}
	
	public function set(string $key, $value, ?int $ref = null) {
		$value = json_encode($value);
		$value = $this->mysqli->real_escape_string($value);
		$SQL = sQuery::select( $this->tablename, "_key = '{$key}' AND _ref " . $this->test( $ref ) );
		if( !$this->mysqli->query( $SQL )->num_rows ) {
			$method = "insert";
			$condition = null;
		} else {
			$method = "update";
			$condition = "_key = '{$key}' AND _ref " . $this->test($ref);
		};
		$Query = sQuery::{$method}( $this->tablename, array( 
			"_key" => $key, 
			"_value" => $value,
			"_ref" => $ref
		), $condition );
		$result = $this->mysqli->query( $Query );
		return $result;
	}
	
	public function get(string $key, ?int $ref = null) {
		$Query = sQuery::select( $this->tablename, "_key = '{$key}' AND _ref " . $this->test( $ref ) );
		$result = $this->mysqli->query( $Query )->fetch_assoc();
		if( $result ) {
			$value = json_decode($result['_value'], true);
			return $value;
		}
	}
	
	public function remove(string $key, ?int $ref = null) {
		$Query = "DELETE FROM `{$this->tablename}` WHERE _key = '{$key}' AND _ref " . $this->test( $ref );
		$result = $this->mysqli->query( $Query );
		return $result;
	}
	
	public function all(?int $ref = null, ?string $regex = null) {
		$data = array();
		if( empty($regex) ) $xpr = null;
		else {
			$regex = str_replace("\\", "\\\\", $regex);
			$xpr = " AND _key REGEXP '{$regex}'";
		};
		$Query = sQuery::select( $this->tablename, "_ref " . $this->test($ref) . $xpr );
		$result = $this->mysqli->query( $Query );
		if( $result->num_rows ) {
			while( $pair = $result->fetch_assoc() ) {
				$key = $pair['_key'];
				$value = json_decode($pair['_value']);
				$data[$key] = $value;
			}
		};
		return $data;
	}
	
}
