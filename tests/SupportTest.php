<?php

namespace SilverStripe\RedirectedURLs\Test;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Support\Arr;
use SilverStripe\RedirectedURLs\Support\StatusCode;
use SilverStripe\Core\Config\Config;

class SupportTest extends SapphireTest
{
    public function testArrayToLowercase(): void
    {
        $array = [
            'Foo' => 'bar',
            'baz' => 'QUX',
        ];

        $this->assertEquals(
            [
                'foo' => 'bar',
                'baz' => 'qux',
            ],
            Arr::toLowercase($array)
        );
    }

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