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
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Config\Config;
use SilverStripe\RedirectedURLs\Service\RedirectedURLInterface;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;
use SilverStripe\RedirectedURLs\Support\Arr;
use SilverStripe\RedirectedURLs\Support\StatusCode;

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
        return Arr::toLowercase((array) $vars);
    }

    protected function getRedirectCode($redirectedURL = false)
    {
        return StatusCode::getRedirectCode($redirectedURL);
    }

    /**
     * @throws HTTPResponse_Exception
     * @param HTTPRequest $request
     */
    public function onBeforeHTTPError404(HTTPRequest $request)
    {
        $service = RedirectedURLService::create();

        $match = $service->findBestRedirectedURLMatch($request);

        if ($match) {
            throw new HTTPResponse_Exception($service->getResponse($match));
        }
    }
}
