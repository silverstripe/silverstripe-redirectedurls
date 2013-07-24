<?php

/**
 * Handles the redirection of any url from a controller. Apply this to your controller using
 *
 * <code>
 * Controller::add_extension("Controller", "RedirectedURLHandler");
 * </code>
 *
 * @package RedirectedURLHandler
 */
class RedirectedURLHandler extends Extension {
	
	/**
	 * Converts an array of key value pairs to lowercase
	 *
	 * @param array $vars key value pairs
	 * @return array
	 */
	function arrayToLowercase($vars) {
		$result = array();
		
		foreach($vars as $k => $v) {
			$result[strtolower($k)] = strtolower($v);
		}
		
		return $result;
	}
	
	/**
	 * @throws SS_HTTPResponse_Exception
	 */
	function onBeforeHTTPError404($request) {
		$base = strtolower($request->getURL());

		$getVars = $this->arrayToLowercase($request->getVars());
		unset($getVars['url']);
				
		// Find all the RedirectedURL objects where the base URL matches. 
		// Assumes the base url has no trailing slash.
		$SQL_base = Convert::raw2sql(rtrim($base, '/'));
		
		$potentials = DataObject::get("RedirectedURL", "\"FromBase\" = '/" . $SQL_base . "'", "\"FromQuerystring\" ASC");

		$matched = null;
		
		// Then check the get vars, ignoring any additional get vars that 
		// this URL may have
		if($potentials) {
			foreach($potentials as $potential) {
				$allVarsMatch = true;
		
				if($potential->FromQuerystring) {
					$reqVars = array();
					parse_str($potential->FromQuerystring, $reqVars);
				
					foreach($reqVars as $k => $v) {
						if(!$v) continue;
						
						if(!isset($getVars[$k]) || $v != $getVars[$k]) {
							$allVarsMatch = false;
							break;
						}
					}
				}
				
				if($allVarsMatch) {
					$matched = $potential;
					break;
				}
			}
		}
		
		// If there's a match, direct!
		if($matched) {
			$response = new SS_HTTPResponse();
			$dest = $matched->To;
			$response->redirect(Director::absoluteURL($dest), 301);
				
			throw new SS_HTTPResponse_Exception($response);
		}
		
		// Otherwise check for default MOSS-fixing.
		if(preg_match('/pages\/default.aspx$/i', $base)) {
			$newBase = preg_replace('/pages\/default.aspx$/i', '', $base);

			$response = new SS_HTTPResponse;
			$response->redirect(Director::absoluteURL($newBase), 301);
			
			throw new SS_HTTPResponse_Exception($response);
		}
		

	}
}