<?php

namespace SilverStripe\RedirectedURLs\Tests\Support;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Support\PathEncoding;

class PathEncodingTest extends SapphireTest
{
    public function testDecodeNonAscii(): void
    {
        $this->assertEquals("caf\u{e9}-menu", PathEncoding::decodeNonAscii('caf%c3%a9-menu'));
        $this->assertEquals("caf\u{e9}-menu", PathEncoding::decodeNonAscii('caf%C3%A9-menu'));

        // Encoded ASCII characters, such as a slash or a space, keep their encoding
        $this->assertEquals('news/a%2fb%20c', PathEncoding::decodeNonAscii('news/a%2fb%20c'));

        // A sequence that isn't valid UTF-8 is left alone
        $this->assertEquals('caf%e9-menu', PathEncoding::decodeNonAscii('caf%e9-menu'));
    }

    public function testEncodeNonAscii(): void
    {
        $this->assertEquals('caf%c3%a9-menu', PathEncoding::encodeNonAscii("caf\u{e9}-menu"));
        $this->assertEquals('news/a b', PathEncoding::encodeNonAscii('news/a b'));
    }

    public function testGetVariants(): void
    {
        $this->assertEquals(
            ['caf%c3%a9-menu', "caf\u{e9}-menu"],
            PathEncoding::getVariants('caf%c3%a9-menu')
        );
        $this->assertEquals(
            ["caf\u{e9}-menu", 'caf%c3%a9-menu'],
            PathEncoding::getVariants("caf\u{e9}-menu")
        );
        $this->assertEquals(['about-us'], PathEncoding::getVariants('about-us'));
    }
}
