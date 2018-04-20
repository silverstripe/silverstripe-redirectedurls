<?php
/**
 * Specifies one URL redirection
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURL extends DataObject implements PermissionProvider {

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

	public function validate() {
		$validation = parent::validate();
		$recordWithSameFrom = self::get()->filter(array(
            'ID:not' => $this->ID,
            'FromBase' => $this->FromBase,
            'FromQuerystring' => $this->FromQuerystring,
        ))->first();
		if ($recordWithSameFrom) {
			$validation->error('There is another record #'.$recordWithSameFrom->ID.' with same FromBase "'.$this->FromBase.'" and FromQuerystring "'.$this->FromQuerystring.'"');
		}
		return $validation;
	}

    public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fromBaseField = $fields->fieldByName('Root.Main.FromBase');
		$fromBaseField->setDescription('e.g. /about-us.html');

		$fromQueryStringField = $fields->fieldByName('Root.Main.FromQuerystring');
		$fromQueryStringField->setDescription('e.g. page=1&num=5');

		$toField = $fields->fieldByName('Root.Main.To');
		$toField->setDescription('e.g. /about?something=5');

		return $fields;
	}

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
		if($val != '/') $val = rtrim($val,'/');
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
	public function findByFrom($from) {
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

 		return RedirectedURL::get()->where("\"FromBase\" = '$SQL_base' $qsClause")->limit(1)->first();
	}

	public function providePermissions() {
		return array(
			'REDIRECTEDURLS_CREATE' => array(
				'name'     => 'Create a redirect',
				'category' => 'Redirects'
			),
			'REDIRECTEDURLS_EDIT'   => array(
				'name'     => 'Edit a redirect',
				'category' => 'Redirects',
			),
			'REDIRECTEDURLS_DELETE' => array(
				'name'     => 'Delete a redirect',
				'category' => 'Redirects',
			)
		);
	}

	public function canView($member = null) {
		return true;
	}

	public function canCreate($member = null) {
		return Permission::check('REDIRECTEDURLS_CREATE');
	}

	public function canEdit($member = null) {
		return Permission::check('REDIRECTEDURLS_EDIT');
	}

	public function canDelete($member = null) {
		return Permission::check('REDIRECTEDURLS_DELETE');
	}

}
