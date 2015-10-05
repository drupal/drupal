<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class HeaderTest extends TestCase
{
    /**
     * test referrer.
     *
     * @group issue130
     */
    public function testIssue130()
    {
        $this->getSession()->visit($this->pathTo('/issue130.php?p=1'));
        $page = $this->getSession()->getPage();

        $page->clickLink('Go to 2');
        $this->assertEquals($this->pathTo('/issue130.php?p=1'), $page->getText());
    }

    public function testHeaders()
    {
        $this->getSession()->setRequestHeader('Accept-Language', 'fr');
        $this->getSession()->visit($this->pathTo('/headers.php'));

        $this->assertContains('[HTTP_ACCEPT_LANGUAGE] => fr', $this->getSession()->getPage()->getContent());
    }

    public function testSetUserAgent()
    {
        $session = $this->getSession();

        $session->setRequestHeader('user-agent', 'foo bar');
        $session->visit($this->pathTo('/headers.php'));
        $this->assertContains('[HTTP_USER_AGENT] => foo bar', $session->getPage()->getContent());
    }

    public function testResetHeaders()
    {
        $session = $this->getSession();

        $session->setRequestHeader('X-Mink-Test', 'test');
        $session->visit($this->pathTo('/headers.php'));

        $this->assertContains(
            '[HTTP_X_MINK_TEST] => test',
            $session->getPage()->getContent(),
            'The custom header should be sent',
            true
        );

        $session->reset();
        $session->visit($this->pathTo('/headers.php'));

        $this->assertNotContains(
            '[HTTP_X_MINK_TEST] => test',
            $session->getPage()->getContent(),
            'The custom header should not be sent after resetting',
            true
        );
    }

    public function testResponseHeaders()
    {
        $this->getSession()->visit($this->pathTo('/response_headers.php'));

        $headers = $this->getSession()->getResponseHeaders();

        $lowercasedHeaders = array();
        foreach ($headers as $name => $value) {
            $lowercasedHeaders[str_replace('_', '-', strtolower($name))] = $value;
        }

        $this->assertArrayHasKey('x-mink-test', $lowercasedHeaders);
    }
}
