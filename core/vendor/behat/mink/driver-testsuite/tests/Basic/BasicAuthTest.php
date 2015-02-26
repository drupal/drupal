<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class BasicAuthTest extends TestCase
{
    /**
     * @dataProvider setBasicAuthDataProvider
     */
    public function testSetBasicAuth($user, $pass, $pageText)
    {
        $session = $this->getSession();

        $session->setBasicAuth($user, $pass);

        $session->visit($this->pathTo('/basic_auth.php'));

        $this->assertContains($pageText, $session->getPage()->getContent());
    }

    public function setBasicAuthDataProvider()
    {
        return array(
            array('mink-user', 'mink-password', 'is authenticated'),
            array('', '', 'is not authenticated'),
        );
    }

    public function testResetBasicAuth()
    {
        $session = $this->getSession();

        $session->setBasicAuth('mink-user', 'mink-password');

        $session->visit($this->pathTo('/basic_auth.php'));

        $this->assertContains('is authenticated', $session->getPage()->getContent());

        $session->setBasicAuth(false);

        $session->visit($this->pathTo('/headers.php'));

        $this->assertNotContains('PHP_AUTH_USER', $session->getPage()->getContent());
    }

    public function testResetWithBasicAuth()
    {
        $session = $this->getSession();

        $session->setBasicAuth('mink-user', 'mink-password');

        $session->visit($this->pathTo('/basic_auth.php'));

        $this->assertContains('is authenticated', $session->getPage()->getContent());

        $session->reset();

        $session->visit($this->pathTo('/headers.php'));

        $this->assertNotContains('PHP_AUTH_USER', $session->getPage()->getContent());
    }
}
