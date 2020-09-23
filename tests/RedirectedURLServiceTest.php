<?php

namespace SilverStripe\RedirectedURLs\Test;

use ReflectionMethod;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\RedirectedURLs\Extension\RedirectedURLHandler;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;

class RedirectedURLServiceTest extends FunctionalTest
{
    public function testArrayToLowercase()
    {
        $array = array('Foo' => 'bar', 'baz' => 'QUX');

        $cont = new RedirectedURLService();

        $arrayToLowercaseMethod = new ReflectionMethod(RedirectedURLService::class, 'arrayToLowercase');
        $arrayToLowercaseMethod->setAccessible(true);

        $this->assertEquals(
            array('foo' => 'bar', 'baz' => 'qux'),
            $arrayToLowercaseMethod->invoke($cont, $array)
        );
    }
}
