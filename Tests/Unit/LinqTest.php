<?php

namespace Linq\Tests\Unit;

use Linq\Tests\Testing\TestCaseEnumerable;

class LinqTest extends TestCaseEnumerable
{
    function testFunctions()
    {
        $this->assertInstanceOf('Linq\Enumerable', from(new \EmptyIterator));
    }
}
