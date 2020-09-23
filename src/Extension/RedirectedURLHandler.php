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
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\Core\Config\Config;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;

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
     * @throws HTTPResponse_Exception
     * @param HTTPRequest $request
     */
    public function onBeforeHTTPError404(HTTPRequest $request)
    {
        /** @var RedirectedURLService $service */
        $service = Injector::inst()->get(RedirectedURLService::class);

        $match = $service->findBestRedirectedURLMatch($request);

        if ($match) {
            throw new HTTPResponse_Exception($service->getResponse($match));
        }
    }
}
