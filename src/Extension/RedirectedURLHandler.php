<?php

namespace SilverStripe\RedirectedURLs\Extension;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ArrayList;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * Handles the redirection of any url from a controller.
 *
 * @package redirectedurls
 * @author sam@silverstripe.com
 * @author scienceninjas@silverstripe.com
 * @property ContentController|ModelAsController|RequestHandler|RedirectedURLHandler $owner
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

        foreach ($vars as $k => $v) {
            if (is_array($v)) {
                $result[strtolower($k)] = $this->arrayToLowercase($v);
            } else {
                $result[strtolower($k)] = strtolower($v);
            }
        }

        return $result;
    }

    /**
     * @throws HTTPResponse_Exception
     * @param HTTPRequest $request
     */
    public function onBeforeHTTPError404($request)
    {
        $base = strtolower($request->getURL());

        $getVars = $this->arrayToLowercase($request->getVars());

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
            foreach ($basepots as $basepot) {
                // If the To URL ends in a wildcard /*, append the remaining request URL elements
                if (substr($basepot->To, -2) === '/*') {
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

                    foreach ($reqVars as $k => $v) {
                        if (!$v) {
                            continue;
                        }

                        if (!isset($getVars[$k]) || $v != $getVars[$k]) {
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
            $response = new HTTPResponse();
            $dest = $matched->To;
            $response->redirect(Director::absoluteURL($dest), 301);

            throw new HTTPResponse_Exception($response);
        }

        // Otherwise check for default MOSS-fixing.
        if (preg_match('/pages\/default.aspx$/i', $base)) {
            $newBase = preg_replace('/pages\/default.aspx$/i', '', $base);

            $response = new HTTPResponse;
            $response->redirect(Director::absoluteURL($newBase), 301);

            throw new HTTPResponse_Exception($response);
        }
    }
}
