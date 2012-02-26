<?php

use Phearman\Worker;

/* Test functions for registering functions. */
function registerTest1($job, $worker) {}
function registerTest2($job, $worker) {}

/* Test class for registering static class methods and instance methods. */
class Test
{
    public static function testMethod1() {}
    public function testMethod2() {}
    public function testMethod3() {}
    public function testMethod4() {}
    public function testMethod5() {}

    public static function testMethod6() {}
    public function testMethod7() {}
    public function testMethod8() {}
}

/* Test class for registering all static/public class methods. */
class TestClass
{
    public static function testClassMethod1() {}
    public function testClassMethod2() {}
}

/* Test class for instantiating and for registering all public instance
 * methods. */
class TestObject
{
    public static function testObjectMethod1() {}
    public function testObjectMethod2() {}
}

/**
 * Worker test case.
 *
 * @author Inash Zubair <inash@leptone.com>
 * @extends PhpUnit_Framework_TestCase
 */
class WorkerTest extends PhpUnit_Framework_TestCase
{
    public function testEchoRequest()
    {
        $worker = new Worker(Config::$servers);
        $string = 'Hello Phearman!';
        $response = $worker->echoRequest($string);
        $this->assertEquals($string, $response->getWorkload());
        return $worker;
    }

    /** @depends testEchoRequest */
    public function testRegisterFunction(Worker $worker)
    {
        /* Test function registration. */
        $worker->register('registerTest1');
        $this->assertTrue($worker->isRegistered('registerTest1'));

        $worker->register('registerTest2', 'registerTest2');
        $this->assertTrue($worker->isRegistered('registerTest2'));
        return $worker;
    }

    /** @depends testRegisterFunction */
    public function testRegisterClass(Worker $worker)
    {
        $worker->register('TestClass');
        $this->assertTrue($worker->isRegistered('testClassMethod1'));
        $this->assertTrue($worker->isRegistered('testClassMethod2'));
        return $worker;
    }


    /** @depends testRegisterClass */
    public function testRegisterClassMethod(Worker $worker)
    {
        /* Test static class method registration. */
        $worker->register('Test', 'testMethod1');
        $this->assertTrue($worker->isRegistered('testMethod1'));

        $worker->register('Test::testMethod2');
        $this->assertTrue($worker->isRegistered('testMethod2'));

        $worker->register('Test::testMethod3');
        $this->assertTrue($worker->isRegistered('testMethod3'));

        $worker->register(array('Test', 'testMethod4'));
        $this->assertTrue($worker->isRegistered('testMethod4'));

        $worker->register('NtestMethod5', array('Test', 'testMethod5'));
        $this->assertTrue($worker->isRegistered('NtestMethod5'));
        return $worker;
    }

    /** @depends testRegisterClassMethod */
    public function testRegisterObject(Worker $worker)
    {
        $object = new TestObject;
        $worker->register($object);
        $this->assertTrue($worker->isRegistered('testObjectMethod1'));
        $this->assertTrue($worker->isRegistered('testObjectMethod2'));
        return $worker;
    }

    /** @depends testRegisterObject */
    public function testRegisterObjectMethod(Worker $worker)
    {
        /* Test objects. */
        $object = new Test;
        $worker->register($object, 'testMethod6');
        $this->assertTrue($worker->isRegistered('testMethod6'));

        $worker->register(array($object, 'testMethod7'));
        $this->assertTrue($worker->isRegistered('testMethod7'));

        $worker->register('NtestMethod8', array($object, 'testMethod8'));
        $this->assertTrue($worker->isRegistered('NtestMethod8'));
        return $worker;
    }

    /** @depends testRegisterObjectMethod */
    public function testClosure(Worker $worker)
    {
        $worker->register('myAnonymous', function($job, $worker) {});
        $this->assertTrue($worker->isRegistered('myAnonymous'));
        return $worker;
    }

    /** @depends testClosure */
    public function testGetRegisteredFunctionNames(Worker $worker)
    {
        $functions = $worker->getRegisteredFunctionNames();
        $this->assertContains('registerTest1', $functions);
        $this->assertContains('registerTest2', $functions);
        $this->assertContains('testClassMethod1', $functions);
        $this->assertContains('testClassMethod2', $functions);
        $this->assertContains('testMethod1', $functions);
        $this->assertContains('testMethod2', $functions);
        $this->assertContains('testMethod3', $functions);
        $this->assertContains('testMethod4', $functions);
        $this->assertContains('NtestMethod5', $functions);
        $this->assertContains('testObjectMethod1', $functions);
        $this->assertContains('testObjectMethod2', $functions);
        $this->assertContains('testMethod6', $functions);
        $this->assertContains('testMethod7', $functions);
        $this->assertContains('NtestMethod8', $functions);
        $this->assertContains('myAnonymous', $functions);
    }
}
