<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class IFrameTest extends TestCase
{
    public function testIFrame()
    {
        $this->getSession()->visit($this->pathTo('/iframe.html'));
        $webAssert = $this->getAssertSession();

        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('Main window div text', $el->getText());

        $this->getSession()->switchToIFrame('subframe');

        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('iFrame div text', $el->getText());

        $this->getSession()->switchToIFrame();

        $el = $webAssert->elementExists('css', '#text');
        $this->assertSame('Main window div text', $el->getText());
    }
}
