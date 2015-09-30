<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class StatusCodeTest extends TestCase
{
    public function testStatuses()
    {
        $this->getSession()->visit($this->pathTo('/index.html'));

        $this->assertEquals(200, $this->getSession()->getStatusCode());
        $this->assertEquals($this->pathTo('/index.html'), $this->getSession()->getCurrentUrl());

        $this->getSession()->visit($this->pathTo('/404.php'));

        $this->assertEquals($this->pathTo('/404.php'), $this->getSession()->getCurrentUrl());
        $this->assertEquals(404, $this->getSession()->getStatusCode());
        $this->assertEquals('Sorry, page not found', $this->getSession()->getPage()->getContent());
    }
}
