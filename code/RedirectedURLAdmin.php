<?php
/**
 * Provides CMS Administration of {@link: RedirectedURL} objects
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURLAdmin extends ModelAdmin {

	/**
	 * @var string
	 */
	private static $url_segment = 'redirects';

	/**
	 * @var string
	 */
	private static $menu_title = 'Redirects';

	/**
	 * @var string
	 */
	private static $menu_icon = 'redirectedurls/images/redirects.png';

	/**
	 * @var array
	 */
	private static $managed_models = array(
		'RedirectedURL'
	);

	/**
	 * Overridden to add duplicate checking to the bulkloader to prevent
	 * multiple records with the same 'FromBase' value.
	 *
	 * Duplicates are found via callback to {@link: RedirectedURL.findByForm}.
	 *
	 * @return array Map of model class names to importer instances
	 */
	public function getModelImporters() {
		return array(
			'RedirectedURL' => new RedirectedURLCsvBulkLoader("RedirectedURL")
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
		foreach(singleton($this->modelClass)->db() as $field => $spec) {
			$fields[$field] = $field;
		}
		return $fields;
	}
}
