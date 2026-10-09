<?php

namespace SilverStripe\RedirectedURLs\Tests\Extension\RedirectedURLHandlerTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * Stands in for an extension like the subsites one, which rules out redirects that don't apply to this request
 *
 * @extends Extension<RedirectedURL>
 */
class HiddenTargetExtension extends Extension implements TestOnly
{
    public function filterBestRedirectedURLMatch(): ?bool
    {
        return $this->getOwner()->To === '/hidden-target' ? false : null;
    }
}
