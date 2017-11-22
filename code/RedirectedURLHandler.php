<?php
/**
 * Handles the redirection of any url from a controller. Apply this to your controller using
 *
 * <code>
 * Controller::add_extension("Controller", "RedirectedURLHandler");
 * </code>
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 */
class RedirectedURLHandler extends Extension {
	/**
	 * Whether to ignore the case of query parameters when attempting to find a RedirectedURL
	 *
	 * @config
	 * @var bool
	 */
	private static $case_insensitive_matching = false;

	/**
	 * Converts the case of the keys of the an array
	 *
	 * @param array $arr Key value pairs
	 * @param int $c Either CASE_LOWER or CASE_UPPER
	 * @return array
	 */
	protected function array_change_key_case_unicode($arr, $c = CASE_LOWER) {
		$c = ($c == CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		$ret = array();
		foreach ($arr as $k => $v) {
			$ret[mb_convert_case($k, $c, "UTF-8")] = $v;
		}
		return $ret;
	}

	/**
	 * Converts an array of key value pairs to lowercase
	 *
	 * @param array $vars key value pairs
	 * @return array
	 */
	protected function arrayToLowercase($vars) {
		$result = array();
		foreach($vars as $k => $v) {
			if(is_array($v)) {
				$result[strtolower($k)] = $this->arrayToLowercase($v);
			} else {
				$result[strtolower($k)] = strtolower($v);
			}
		}
		return $result;
	}

	/**
	 * @throws SS_HTTPResponse_Exception
	 */
	public function onBeforeHTTPError404($request) {
		$caseInsensitiveMatching = $this->config()->get('case_insensitive_matching');
		$base = $request->getURL();
		$getVars = $request->getVars();

		unset($getVars['url']);

		if ($caseInsensitiveMatching) {
			$getVars = $this->array_change_key_case_unicode($getVars);
		}

		// Find all the RedirectedURL objects where the base URL matches.
		// Assumes the base url has no trailing slash.
		$SQL_base = Convert::raw2sql(rtrim($base, '/'));

		$potentials = RedirectedURL::get()->filter(array('FromBase' => '/' . $SQL_base))->sort('FromQuerystring ASC');
		$listPotentials = new ArrayList;
		foreach ($potentials as $potential) {
			$listPotentials->push($potential);
		}

		// Find any matching FromBase elements terminating in a wildcard /*
		$baseparts = explode('/', $base);
		for ($pos = count($baseparts) - 1; $pos >= 0; $pos--) {
			$basestr = implode('/', array_slice($baseparts, 0, $pos));
			$basepart = Convert::raw2sql($basestr . '/*');
			$basepots = RedirectedURL::get()->filter(array('FromBase' => '/' . $basepart))->sort('FromQuerystring ASC');
			foreach ($basepots as $basepot){
				// If the To URL ends in a wildcard /*, append the remaining request URL elements
				if (substr($basepot->To, -2) === '/*'){
					$basepot->To = substr($basepot->To, 0, -2) . substr($base, strlen($basestr));
				}
				$listPotentials->push($basepot);
			}
		}

		$matched = null;

		// Then check the get vars, ignoring any additional get vars that
		// this URL may have
		if ($listPotentials) {
			foreach ($listPotentials as $potential) {
				$allVarsMatch = true;

				if ($potential->FromQuerystring) {
					$reqVars = array();
					parse_str($potential->FromQuerystring, $reqVars);
					if ($caseInsensitiveMatching) {
						$reqVars = $this->array_change_key_case_unicode($reqVars);
					}

					foreach ($reqVars as $k => $v) {
						if (!$v) continue;

						if (!isset($getVars[$k]) ||
							($caseInsensitiveMatching && strcasecmp($v, $getVars[$k]) !== 0) ||
							(!$caseInsensitiveMatching && strcmp($v, $getVars[$k]) !== 0)
						) {
							$allVarsMatch = false;
							break;
						}
					}
				}

				if ($allVarsMatch) {
					$matched = $potential;
					break;
				}
			}
		}

		// If there's a match, direct!
		if ($matched) {
			$response = new SS_HTTPResponse();
			$dest = $matched->To;
			$response->redirect(Director::absoluteURL($dest), 301);

			throw new SS_HTTPResponse_Exception($response);
		}

		// Otherwise check for default MOSS-fixing.
		if (preg_match('/pages\/default.aspx$/i', $base)) {
			$newBase = preg_replace('/pages\/default.aspx$/i', '', $base);

			$response = new SS_HTTPResponse;
			$response->redirect(Director::absoluteURL($newBase), 301);

			throw new SS_HTTPResponse_Exception($response);
		}
	}
}
