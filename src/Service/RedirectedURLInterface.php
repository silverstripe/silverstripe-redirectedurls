<?php

namespace SilverStripe\RedirectedURLs\Service;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

interface RedirectedURLInterface
{
    /**
     * Based on the current HTTP request, find the best redirected URL data defined in the database.
     */
    public function findBestRedirectedURLMatch(HTTPRequest $request): ?RedirectedURL;

    /**
     * Get an instance of HTTP response using the data from the redirected URL object.
     * The returned response object is to replace the current HTTP response (i.e. redirect to new page/file).
     */
    public function getResponse(RedirectedURL $redirect): HTTPResponse;
}
