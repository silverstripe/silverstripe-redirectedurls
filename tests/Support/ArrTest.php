<?php

namespace SilverStripe\RedirectedURLs\Tests\Support;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Support\Arr;

class ArrTest extends SapphireTest
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
}
