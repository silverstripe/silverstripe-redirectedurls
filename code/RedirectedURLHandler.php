<?php

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;

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
class RedirectedURLHandler extends Extension
{

	/**
	 * Converts an array of key value pairs to lowercase
	 *
	 * @param array $vars key value pairs
	 * @return array
	 */
	protected function arrayToLowercase($vars)
    {
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
     * @param HTTPRequest $request
     */
	public function onBeforeHTTPError404(HTTPRequest $request)
    {
        $base = strtolower($request->getURL());

		$getVars = $this->arrayToLowercase($request->getVars());
		unset($getVars['url']);

		// Find all the RedirectedURL objects where the base URL matches.
		// Assumes the base url has no trailing slash.
		$SQL_base = Convert::raw2sql(rtrim($base, '/'));

		// Get the potential URLS from the RedirectorURL data store
        $potentials = RedirectedURL::get()
            ->filter('FromBase', '/' . $SQL_base)
            ->sort('FromQuerystring', 'ASC');

		$listPotentials = new ArrayList;
		foreach  ($potentials as $potential){
			$listPotentials->push($potential);
		}
		
		// Find any matching FromBase elements terminating in a wildcard /*
		$baseparts = explode('/', $base);
		for ($pos = count($baseparts) - 1; $pos >= 0; $pos--){
			$basestr = implode('/', array_slice($baseparts, 0, $pos));
			$basepart = Convert::raw2sql($basestr . '/*');
			$basepots = DataObject::get("RedirectedURL", "\"FromBase\" = '/" . $basepart . "'", "\"FromQuerystring\" ASC");
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
		if($listPotentials) {
			foreach($listPotentials as $potential) {
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
        if ($matched) {
            $destination = $matched->To;
            $baseUrl = Director::absoluteBaseURL();

            $res = new HTTPResponse();

            $res->redirect(
                Controller::join_links(
                    $baseUrl,
                    $destination
                ),
                301
            );

            throw new HTTPResponse_Exception($res);
		}

		// Otherwise check for default MOSS-fixing.
		if(preg_match('/pages\/default.aspx$/i', $base)) {
			$newBase = preg_replace('/pages\/default.aspx$/i', '', $base);

			$response = new HTTPResponse();
			$response->redirect(Director::absoluteURL($newBase), 301);

			throw new HTTPResponse_Exception($response);
		}
	}
}
