<?php

namespace Linq\Tests\Unit;

use Linq\Functions as F;
use Linq\Tests\Testing\TestCaseEnumerable;

/** @covers \Linq\Functions
 */
class FunctionsTest extends TestCaseEnumerable
{
    /** @covers \Linq\Functions::init
     */
    function testInit()
    {
        F::init();
        $this->assertNotEmpty(F::$identity);
    }

    function testIdentity()
    {
        $f = F::$identity;
        $this->assertSame(2, $f(2));
    }

    function testKey()
    {
        $f = F::$key;
        $this->assertSame(3, $f(2, 3));
    }

    function testValue()
    {
        $f = F::$value;
        $this->assertSame(2, $f(2, 3));
    }

    function testTrue()
    {
        $f = F::$true;
        $this->assertSame(true, $f());
    }

    function testFalse()
    {
        $f = F::$false;
        $this->assertSame(false, $f());
    }

    function testBlank()
    {
        $f = F::$blank;
        $this->assertSame(null, $f());
    }

    function testCompareStrict()
    {
        $f = F::$compareStrict;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(-1, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    function testCompareStrictReversed()
    {
        $f = F::$compareStrictReversed;
        $this->assertSame(1, $f(2, 3));
        $this->assertSame(1, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(-1, $f(3, 2));
    }

    function testCompareLoose()
    {
        $f = F::$compareLoose;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(0, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    function testCompareLooseReversed()
    {
        $f = F::$compareLooseReversed;
        $this->assertSame(1, $f(2, 3));
        $this->assertSame(0, $f(2, '2'));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(-1, $f(3, 2));
    }

    function testCompareInt()
    {
        $f = F::$compareInt;
        $this->assertSame(-1, $f(2, 3));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(1, $f(3, 2));
    }

    function testCompareIntReversed()
    {
        $f = F::$compareIntReversed;
        $this->assertSame(1, $f(2, 3));
        $this->assertSame(0, $f(2, 2));
        $this->assertSame(-1, $f(3, 2));
    }

    function testIncrement()
    {
        $f = F::increment();
        $this->assertSame(0, $f());
        $this->assertSame(1, $f());
        $this->assertSame(2, $f());

        $g = F::increment();
        $this->assertSame(0, $g());
        $this->assertSame(1, $g());
        $this->assertSame(3, $f());
    }
}
