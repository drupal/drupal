<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

/**
 * This testcase ensures that the driver implementation follows recommended practices for drivers.
 */
class BestPracticesTest extends TestCase
{
    public function testExtendsCoreDriver()
    {
        $driver = $this->createDriver();

        $this->assertInstanceOf('Behat\Mink\Driver\CoreDriver', $driver);

        return $driver;
    }

    /**
     * @depends testExtendsCoreDriver
     */
    public function testImplementFindXpath()
    {
        $driver = $this->createDriver();

        $this->assertNotImplementMethod('find', $driver, 'The driver should overwrite `findElementXpaths` rather than `find` for forward compatibility with Mink 2.');
        $this->assertImplementMethod('findElementXpaths', $driver, 'The driver must be able to find elements.');
        $this->assertNotImplementMethod('setSession', $driver, 'The driver should not deal with the Session directly for forward compatibility with Mink 2.');
    }

    /**
     * @dataProvider provideRequiredMethods
     */
    public function testImplementBasicApi($method)
    {
        $driver = $this->createDriver();

        $this->assertImplementMethod($method, $driver, 'The driver is unusable when this method is not implemented.');
    }

    public function provideRequiredMethods()
    {
        return array(
            array('start'),
            array('isStarted'),
            array('stop'),
            array('reset'),
            array('visit'),
            array('getCurrentUrl'),
            array('getContent'),
            array('click'),
        );
    }

    private function assertImplementMethod($method, $object, $reason = '')
    {
        $ref = new \ReflectionClass(get_class($object));
        $refMethod = $ref->getMethod($method);

        $message = sprintf('The driver should implement the `%s` method.', $method);

        if ('' !== $reason) {
            $message .= ' '.$reason;
        }

        $this->assertSame($ref->name, $refMethod->getDeclaringClass()->name, $message);
    }

    private function assertNotImplementMethod($method, $object, $reason = '')
    {
        $ref = new \ReflectionClass(get_class($object));
        $refMethod = $ref->getMethod($method);

        $message = sprintf('The driver should not implement the `%s` method.', $method);

        if ('' !== $reason) {
            $message .= ' '.$reason;
        }

        $this->assertNotSame($ref->name, $refMethod->getDeclaringClass()->name, $message);
    }
}
