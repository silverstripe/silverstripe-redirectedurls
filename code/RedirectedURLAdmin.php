<?php
/**
 * Provides CMS Administration of {@link: RedirectedURL} objects
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURLAdmin extends ModelAdmin {

	private static $url_segment = 'redirects';
	private static $menu_title = 'Redirects';

	private static $managed_models = array(
		'RedirectedURL',
	);

	public function init() {
		parent::init();
		Requirements::javascript('mysite/javascript/AdminCenterColumnWide.js');
	}

	public function getModelImporters() {
		$importer = new CsvBulkLoader("RedirectedURL");
		$importer->duplicateChecks = array(
			'FromBase' => array('callback' => 'findByFrom'),
		);
		return array(
			'RedirectedURL' => $importer
		);
	}

	/**
	 * Overriden so that the CSV column headings have the exact field names of the DataObject
	 *
	 * To prevent field name conversion in DataObject::summaryFields() during export
	 * e.g. 'FromBase' is output as 'From Base'
	 *
	 * @return array
	 */
	public function getExportFields() {
		$fields = array();
		$modelStaticFields = singleton($this->modelClass)->stat('db');
		foreach($modelStaticFields as $field => $spec) {
			$fields[$field] = $field;
		}
		return $fields;
	}
}
