<?php

class RedirectedURLAdmin extends ModelAdmin {
	static $url_segment = 'redirects';
	static $menu_title = 'Redirects';
	
	static $managed_models = array(
		'RedirectedURL',
	);
	
	function getModelImporters() {
		$importer = new CsvBulkLoader("RedirectedURL");
		$importer->duplicateChecks = array(
			'FromBase' => array('callback' => 'findByFrom'),
		);
		return array(
			'RedirectedURL' => $importer
		);
	}
	
	/**
	 * Overriden so that the CSV column is exactly as the output 
	 * 
	 * DataObject::summary_fields() get this wrong by separate words, ie FromBase => From Base which confuses the 
	 * importer later.
	 * 
	 * @return array
	 */
	public function getExportFields() {
		return array(
			"FromBase",
			"FromQuerystring",
			"To",
		);
	}

	public function init() {
		parent::init();
		Requirements::javascript('mysite/javascript/AdminCenterColumnWide.js');
	}
}