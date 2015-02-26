<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class VisibilityTest extends TestCase
{
    public function testVisibility()
    {
        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $webAssert = $this->getAssertSession();

        $clicker   = $webAssert->elementExists('css', '.elements div#clicker');
        $invisible = $webAssert->elementExists('css', '#invisible');

        $this->assertFalse($invisible->isVisible());
        $this->assertTrue($clicker->isVisible());
    }
}
