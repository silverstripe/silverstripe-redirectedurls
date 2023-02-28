<?php

namespace SilverStripe\RedirectedURLs\Service;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Support\Arr;
use SilverStripe\RedirectedURLs\Support\StatusCode;

class RedirectedURLService implements RedirectedURLInterface
{
    use Extensible;
    use Configurable;
    use Injectable;

    public function findBestRedirectedURLMatch(HTTPRequest $request): ?RedirectedURL
    {
        $base = strtolower($request->getURL());
        $getVars = Arr::toLowercase($request->getVars());

        // Find all the RedirectedURL objects where the base URL matches.
        // Assumes the base url has no trailing slash.
        $SQL_base = Convert::raw2sql(rtrim($base, '/'));

        $potentials = RedirectedURL::get()->filter(['FromBase' => '/' . $SQL_base])->sort('FromQuerystring DESC');
        /** @var ArrayList|RedirectedURL[] $listPotentials */
        $listPotentials = new ArrayList();

        foreach ($potentials as $potential) {
            $listPotentials->push($potential);
        }

        // Find any matching FromBase elements terminating in a wildcard /*
        $baseParts = explode('/', $base);

        for ($pos = count($baseParts) - 1; $pos >= 0; $pos--) {
            $baseStr = implode('/', array_slice($baseParts, 0, $pos));
            $basePart = Convert::raw2sql($baseStr . '/*');
            $basePots = RedirectedURL::get()->filter(['FromBase' => '/' . $basePart])->sort('FromQuerystring DESC');

            foreach ($basePots as $basePot) {
                // If the To URL ends in a wildcard /*, append the remaining request URL elements
                if ($basePot->RedirectionType === 'External' && substr($basePot->To, -2) === '/*') {
                    $basePot->To = substr($basePot->To, 0, -2) . substr($base, strlen($baseStr));
                }

                $listPotentials->push($basePot);
            }
        }

        // If there are no potential matches, then we can exit early and return null
        if ($listPotentials->count() === 0) {
            return null;
        }

        $matched = null;

        // Then check the get vars, ignoring any additional get vars that this URL may have
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

        // If we found a match, we return it - otherwise we return null to indicate that no match was found
        return $matched;
    }

    public function getResponse(RedirectedURL $redirect): HTTPResponse
    {
        $response = HTTPResponse::create()
            ->redirect(Director::absoluteURL($redirect->Link() ?? ''), StatusCode::getRedirectCode($redirect));

        return $response;
    }
}
