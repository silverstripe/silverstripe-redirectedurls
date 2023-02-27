<?php

namespace SilverStripe\RedirectedURLs\Extension;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Extension;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;

/**
 * Handles the redirection of any url from a controller.
 *
 * @property ContentController|ModelAsController|RequestHandler|RedirectedURLHandler $owner
 */
class RedirectedURLHandler extends Extension
{
    /**
     * @throws HTTPResponse_Exception
     */
    public function onBeforeHTTPError404(HTTPRequest $request): void
    {
        $service = RedirectedURLService::create();

        $match = $service->findBestRedirectedURLMatch($request);

        if ($match) {
            throw new HTTPResponse_Exception($service->getResponse($match));
        }
    }
}
