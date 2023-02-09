<?php

namespace SilverStripe\RedirectedURLs\Tests\Support;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Support\StatusCode;

class StatusCodeTest extends SapphireTest
{
    public function testRedirectCode():void
    {
        $defaultRedirectCode = (int) Config::inst()->get(RedirectedURL::class, 'default_redirect_code');
        $this->assertEquals($defaultRedirectCode, StatusCode::getRedirectCode());

        $redirectObject = RedirectedURL::create([
            'RedirectCode' => 301,
        ]);
        $this->assertEquals(301, StatusCode::getRedirectCode($redirectObject));
    }
}
