<?php

namespace SilverStripe\RedirectedURLs\Extension;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;

/**
 * Class AssetStoreURLHandler
 * @package SilverStripe\RedirectedURLs\Extension
 *
 * This extension applies to FlysystemAssetStore, and ensures that an appropriate redirect response is returned when an
 * asset isn't found and the path matches a {@link RedirectedURL} object.
 */
class AssetStoreURLHandler extends Extension
{
    /**
     * @var array An array of HTTP status codes that should be acted upon if they are returned by the AssetStore.
     * @config
     */
    private static $act_upon = [
        404
    ];

    public function updateResponse(HTTPResponse &$response, string $asset, array $context = [])
    {
        // Only change the response if the response provided by FlysystemAssetStore matches one we should act on
        if (in_array($response->getStatusCode(), $this->owner->config()->act_upon)) {
            // Get the current request, then attempt to find a RedirectedURL object that matches
            if (Controller::has_curr()) {
                $controller = Controller::curr();
                $request = $controller->getRequest();

                /** @var RedirectedURLService $service */
                $service = Injector::inst()->get(RedirectedURLService::class);
                $match = $service->findBestRedirectedURLMatch($request);

                if ($match) {
                    // We have a matching RedirectedURL, so replace the base HTTPResponse provided by
                    // FlysystemAssetStore with our redirect response
                    $response = $service->getResponse($match);
                }
            }
        }
    }
}
