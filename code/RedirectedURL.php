<?php

/**
 * Each of these records specifies one URL redirection
 */
class RedirectedURL extends DataObject {
	static $db = array(
		"FromBase" => "Varchar(255)",
		"FromQuerystring" => "Varchar(255)",
		"To" => "Varchar(255)",
	);
	static $indexes = array(
		"From" => array(
			'type' => 'unique',
			'value' => '"FromBase","FromQuerystring"',
		)
	);
	static $summary_fields = array(
		"FromBase",
		"FromQuerystring",
		"To",
	);
	static $searchable_fields = array(
		"FromBase",
		"FromQuerystring",
		"To",
	);
	
	function setFrom($val) {
		if(strpos($val,'?') !== false) {
			list($base, $querystring) = explode('?', $val, 2);
		} else {
			$base = $val;
			$querystring = null;
		}
		$this->setFromBase($base);
		$this->setFromQuerystring($querystring);
	}
	function getFrom() {
		$url = $this->FromBase;
		if($this->FromQuerystring) $url .= "?" . $this->FromQuerystring;
		return $url;
	}
	
	function setFromBase($val) {
		if($val[0] != '/') $val = "/$val";
		$val = rtrim($val,'?');
		$this->setField('FromBase', strtolower($val));
	}
	function setFromQuerystring($val) {
		$val = rtrim($val,'?');
		$this->setField('FromQuerystring', strtolower($val));
	}
	
	// Helper for bulkloader
	function findByFrom($from) {
		if($from[0] != '/') $from = "/$from";
		$from = rtrim($from,'?');

		if(strpos($from,'?') !== false) {
			list($base, $querystring) = explode('?', strtolower($from), 2);
			
		} else {
			$base = $from;
			$querystring = null;
		}
		
		$SQL_base = Convert::raw2sql($base);
		$SQL_querystring = Convert::raw2sql($querystring);
		
		if($querystring) $qsClause = "AND \"FromQuerystring\" = '$SQL_querystring'";
		else $qsClause = "AND \"FromQuerystring\" IS NULL";
		
 		return DataObject::get_one("RedirectedURL", "\"FromBase\" = '$SQL_base' $qsClause");
	}
	
}
