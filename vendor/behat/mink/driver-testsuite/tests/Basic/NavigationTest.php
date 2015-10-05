<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class NavigationTest extends TestCase
{
    public function testRedirect()
    {
        $this->getSession()->visit($this->pathTo('/redirector.php'));
        $this->assertEquals($this->pathTo('/redirect_destination.html'), $this->getSession()->getCurrentUrl());
    }

    public function testPageControls()
    {
        $this->getSession()->visit($this->pathTo('/randomizer.php'));
        $number1 = $this->getAssertSession()->elementExists('css', '#number')->getText();

        $this->getSession()->reload();
        $number2 = $this->getAssertSession()->elementExists('css', '#number')->getText();

        $this->assertNotEquals($number1, $number2);

        $this->getSession()->visit($this->pathTo('/links.html'));
        $this->getSession()->getPage()->clickLink('Random number page');

        $this->assertEquals($this->pathTo('/randomizer.php'), $this->getSession()->getCurrentUrl());

        $this->getSession()->back();
        $this->assertEquals($this->pathTo('/links.html'), $this->getSession()->getCurrentUrl());

        $this->getSession()->forward();
        $this->assertEquals($this->pathTo('/randomizer.php'), $this->getSession()->getCurrentUrl());
    }

    public function testLinks()
    {
        $this->getSession()->visit($this->pathTo('/links.html'));
        $page = $this->getSession()->getPage();
        $link = $page->findLink('Redirect me to');

        $this->assertNotNull($link);
        $this->assertRegExp('/redirector\.php$/', $link->getAttribute('href'));
        $link->click();

        $this->assertEquals($this->pathTo('/redirect_destination.html'), $this->getSession()->getCurrentUrl());

        $this->getSession()->visit($this->pathTo('/links.html'));
        $page = $this->getSession()->getPage();
        $link = $page->findLink('basic form image');

        $this->assertNotNull($link);
        $this->assertRegExp('/basic_form\.html$/', $link->getAttribute('href'));
        $link->click();

        $this->assertEquals($this->pathTo('/basic_form.html'), $this->getSession()->getCurrentUrl());

        $this->getSession()->visit($this->pathTo('/links.html'));
        $page = $this->getSession()->getPage();
        $link = $page->findLink('Link with a ');

        $this->assertNotNull($link);
        $this->assertRegExp('/links\.html\?quoted$/', $link->getAttribute('href'));
        $link->click();

        $this->assertEquals($this->pathTo('/links.html?quoted'), $this->getSession()->getCurrentUrl());
    }
}
