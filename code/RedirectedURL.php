<?php
/**
 * Specifies one URL redirection
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURL extends DataObject {

	private static $singular_name = 'Redirected URL';

	private static $db = array(
		'FromBase' => 'Varchar(255)',
		'FromQuerystring' => 'Varchar(255)',
		'To' => 'Varchar(255)',
	);

	private static $indexes = array(
		'From' => array(
			'type' => 'unique',
			'value' => '"FromBase","FromQuerystring"',
		)
	);

	private static $summary_fields = array(
		'FromBase' => 'From URL base',
		'FromQuerystring' => 'From URL query parameters',
		'To' => 'To URL',
	);

	private static $searchable_fields = array(
		'FromBase',
		'FromQuerystring',
		'To',
	);

	public function setFrom($val) {
		if(strpos($val,'?') !== false) {
			list($base, $querystring) = explode('?', $val, 2);
		} else {
			$base = $val;
			$querystring = null;
		}
		$this->setFromBase($base);
		$this->setFromQuerystring($querystring);
	}

	public function getFrom() {
		$url = $this->FromBase;
		if($this->FromQuerystring) $url .= "?" . $this->FromQuerystring;
		return $url;
	}

	public function setFromBase($val) {
		if($val[0] != '/') $val = "/$val";
		$val = rtrim($val,'?');
		$this->setField('FromBase', strtolower($val));
	}

	public function setFromQuerystring($val) {
		$val = rtrim($val,'?');
		$this->setField('FromQuerystring', strtolower($val));
	}

	/**
	 * Helper for bulkloader {@link: RedirectedURLAdmin.getModelImporters}
	 *
	 * @param string $from The From URL to search
	 * @return DataObject {@link: RedirectedURL}
	 */
	protected function findByFrom($from) {
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
