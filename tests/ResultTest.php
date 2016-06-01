<?php

namespace Tests;

use function Result\bind;
use function Result\fail;
use function Result\ifFail;
use function Result\ifOk;
use function Result\isFail;
use function Result\isOk;
use function Result\ok;
use function Result\pipeline;
use function Result\tryCatch;
use function Result\typeOf;
use function Result\valueOf;

use const Result\RESULT_ERROR;
use const Result\RESULT_OK;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testSuccessResult()
    {
        $result = ok('foo');

        $this->assertEquals('foo', valueOf($result));
        $this->assertEquals(RESULT_OK, typeOf($result));
        $this->assertTrue(isOk($result));

        $flag = false;

        ifOk($result, function ($value) use (&$flag) {
            $this->assertEquals('foo', $value);
            $flag = true;
        });

        $this->assertTrue($flag);
    }

    public function testErrorResult()
    {
        $result = fail('foo');

        $this->assertEquals('foo', valueOf($result));
        $this->assertEquals(RESULT_ERROR, typeOf($result));
        $this->assertTrue(isFail($result));

        $flag = false;

        ifFail($result, function ($value) use (&$flag) {
            $this->assertEquals('foo', $value);
            $flag = true;
        });

        $this->assertTrue($flag);
    }

    public function testBadMethodCall()
    {
        $r1 = ok();
        $r2 = fail();

        try {
            $r1('foo');
            $this->fail();
        } catch (\BadMethodCallException $exception) {
        }

        try {
            $r2('foo');
            $this->fail();
        } catch (\BadMethodCallException $exception) {
        }
    }

    public function testTryCatch()
    {
        $result = tryCatch(function () {
            return 'foo';
        });


        $this->assertTrue(isOk($result));
        $this->assertEquals('foo', valueOf($result));

        $result = tryCatch(function () {
            throw new \Exception('bar');
        });

        $this->assertTrue(isFail($result));
        $this->assertInstanceOf(\Exception::class, valueOf($result));

        $result = tryCatch(function () {
            throw new \Exception('baz');
        }, function (\Exception $exception) {
            return $exception->getMessage();
        });

        $this->assertTrue(isFail($result));
        $this->assertEquals('baz', valueOf($result));
    }

    public function testBind()
    {
        $bindFunction = function ($value) {
            return $value != 0
                ? ok(100 / $value)
                : fail('Division by zero');
        };

        $result = bind(ok(5), $bindFunction);

        $this->assertTrue(isOk($result));
        $this->assertEquals(20, valueOf($result));

        $result = bind(fail('foo'), $bindFunction);

        $this->assertTrue(isFail($result));
        $this->assertEquals('foo', valueOf($result));

        $result = bind(ok(0), $bindFunction);

        $this->assertTrue(isFail($result));
        $this->assertEquals('Division by zero', valueOf($result));
    }

    public function testPipeline()
    {
        $f1 = function ($value) {
            return ok($value * 2);
        };

        $f2 = function () {
            return fail('Error');
        };

        $f3 = function ($value) {
            return ok($value + 10);
        };

        $pipeline = pipeline($f1, $f3);

        $result = $pipeline(5);

        $this->assertTrue(isOk($result));
        $this->assertEquals(20, valueOf($result));

        $pipeline = pipeline($f1, $f2, $f3);

        $result = $pipeline(5);

        $this->assertTrue(isFail($result));
        $this->assertEquals('Error', valueOf($result));
    }
}