<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;

class WindowNameTest extends TestCase
{
    public function testWindowNames()
    {
        $session = $this->getSession();
        $session->start();

        $windowNames = $session->getWindowNames();
        $this->assertArrayHasKey(0, $windowNames);

        $windowName = $session->getWindowName();

        $this->assertInternalType('string', $windowName);
        $this->assertContains($windowName, $windowNames, 'The current window name is one of the available window names.');
    }
}
