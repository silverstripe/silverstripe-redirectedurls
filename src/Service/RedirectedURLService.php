<?php

namespace SilverStripe\RedirectedURLs\Service;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\ORM\ArrayList;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

class RedirectedURLService
{
    use Extensible, Configurable;

    /**
     * @param HTTPRequest $request
     * @return RedirectedURL|null
     */
    public function findBestRedirectedURLMatch(HTTPRequest $request): ?RedirectedURL
    {
        $base = strtolower($request->getURL());
        $getVars = $this->arrayToLowercase($request->getVars());

        // Find all the RedirectedURL objects where the base URL matches.
        // Assumes the base url has no trailing slash.
        $SQL_base = Convert::raw2sql(rtrim($base, '/'));

        $potentials = RedirectedURL::get()->filter(['FromBase' => '/' . $SQL_base])->sort('FromQuerystring DESC');
        $listPotentials = new ArrayList();
        foreach ($potentials as $potential) {
            $listPotentials->push($potential);
        }

        // Find any matching FromBase elements terminating in a wildcard /*
        $baseparts = explode('/', $base);
        for ($pos = count($baseparts) - 1; $pos >= 0; $pos--) {
            $basestr = implode('/', array_slice($baseparts, 0, $pos));
            $basepart = Convert::raw2sql($basestr . '/*');
            $basepots = RedirectedURL::get()->filter(['FromBase' => '/' . $basepart])->sort('FromQuerystring DESC');
            foreach ($basepots as $basepot) {
                // If the To URL ends in a wildcard /*, append the remaining request URL elements
                if ($basepot->RedirectionType === 'External' && substr($basepot->To, -2) === '/*') {
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

        // If we found a match, we return it - otherwise we return null to indicate that no match was found
        return $matched;
    }

    public function getResponse(RedirectedURL $redirect): HTTPResponse
    {
        $response = HTTPResponse::create()
            ->redirect(Director::absoluteURL($redirect->Link()), $this->getRedirectCode($redirect));

        return $response;
    }

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

    public function getRedirectCode($redirectedURL = null)
    {
        if ($redirectedURL instanceof RedirectedURL) {
            if (isset($redirectedURL->RedirectCode) && intval($redirectedURL->RedirectCode) > 0) {
                return intval($redirectedURL->RedirectCode);
            }
        }

        $redirectCode = 301;
        $defaultRedirectCode = intval(Config::inst()->get(RedirectedURL::class, 'default_redirect_code'));
        if ($defaultRedirectCode > 0) {
            $redirectCode = $defaultRedirectCode;
        }

        return $redirectCode;
    }
}
