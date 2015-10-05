<?php

namespace Behat\Mink\Tests\Driver\Basic;

use Behat\Mink\Tests\Driver\TestCase;

class CookieTest extends TestCase
{
    /**
     * test cookie decoding.
     *
     * @group issue140
     */
    public function testIssue140()
    {
        $this->getSession()->visit($this->pathTo('/issue140.php'));

        $this->getSession()->getPage()->fillField('cookie_value', 'some:value;');
        $this->getSession()->getPage()->pressButton('Set cookie');

        $this->getSession()->visit($this->pathTo('/issue140.php?show_value'));
        $this->assertEquals('some:value;', $this->getSession()->getCookie('tc'));
        $this->assertEquals('some:value;', $this->getSession()->getPage()->getText());
    }

    public function testCookie()
    {
        $this->getSession()->visit($this->pathTo('/cookie_page2.php'));
        $this->assertContains('Previous cookie: NO', $this->getSession()->getPage()->getText());
        $this->assertNull($this->getSession()->getCookie('srvr_cookie'));

        $this->getSession()->setCookie('srvr_cookie', 'client cookie set');
        $this->getSession()->reload();
        $this->assertContains('Previous cookie: client cookie set', $this->getSession()->getPage()->getText());
        $this->assertEquals('client cookie set', $this->getSession()->getCookie('srvr_cookie'));

        $this->getSession()->setCookie('srvr_cookie', null);
        $this->getSession()->reload();
        $this->assertContains('Previous cookie: NO', $this->getSession()->getPage()->getText());

        $this->getSession()->visit($this->pathTo('/cookie_page1.php'));
        $this->getSession()->visit($this->pathTo('/cookie_page2.php'));

        $this->assertContains('Previous cookie: srv_var_is_set', $this->getSession()->getPage()->getText());
        $this->getSession()->setCookie('srvr_cookie', null);
        $this->getSession()->reload();
        $this->assertContains('Previous cookie: NO', $this->getSession()->getPage()->getText());
    }

    /**
     * @dataProvider cookieWithPathsDataProvider
     */
    public function testCookieWithPaths($cookieRemovalMode)
    {
        // start clean
        $session = $this->getSession();
        $session->visit($this->pathTo('/sub-folder/cookie_page2.php'));
        $this->assertContains('Previous cookie: NO', $session->getPage()->getText());

        // cookie from root path is accessible in sub-folder
        $session->visit($this->pathTo('/cookie_page1.php'));
        $session->visit($this->pathTo('/sub-folder/cookie_page2.php'));
        $this->assertContains('Previous cookie: srv_var_is_set', $session->getPage()->getText());

        // cookie from sub-folder overrides cookie from root path
        $session->visit($this->pathTo('/sub-folder/cookie_page1.php'));
        $session->visit($this->pathTo('/sub-folder/cookie_page2.php'));
        $this->assertContains('Previous cookie: srv_var_is_set_sub_folder', $session->getPage()->getText());

        if ($cookieRemovalMode == 'session_reset') {
            $session->reset();
        } elseif ($cookieRemovalMode == 'cookie_delete') {
            $session->setCookie('srvr_cookie', null);
        }

        // cookie is removed from all paths
        $session->visit($this->pathTo('/sub-folder/cookie_page2.php'));
        $this->assertContains('Previous cookie: NO', $session->getPage()->getText());
    }

    public function cookieWithPathsDataProvider()
    {
        return array(
            array('session_reset'),
            array('cookie_delete'),
        );
    }

    public function testReset()
    {
        $this->getSession()->visit($this->pathTo('/cookie_page1.php'));
        $this->getSession()->visit($this->pathTo('/cookie_page2.php'));
        $this->assertContains('Previous cookie: srv_var_is_set', $this->getSession()->getPage()->getText());

        $this->getSession()->reset();
        $this->getSession()->visit($this->pathTo('/cookie_page2.php'));

        $this->assertContains('Previous cookie: NO', $this->getSession()->getPage()->getText());

        $this->getSession()->setCookie('srvr_cookie', 'test_cookie');
        $this->getSession()->visit($this->pathTo('/cookie_page2.php'));
        $this->assertContains('Previous cookie: test_cookie', $this->getSession()->getPage()->getText());
        $this->getSession()->reset();
        $this->getSession()->visit($this->pathTo('/cookie_page2.php'));
        $this->assertContains('Previous cookie: NO', $this->getSession()->getPage()->getText());

        $this->getSession()->setCookie('client_cookie1', 'some_val');
        $this->getSession()->setCookie('client_cookie2', 123);
        $this->getSession()->visit($this->pathTo('/session_test.php'));
        $this->getSession()->visit($this->pathTo('/cookie_page1.php'));

        $this->getSession()->visit($this->pathTo('/print_cookies.php'));
        $this->assertContains(
            "'client_cookie1' = 'some_val'",
            $this->getSession()->getPage()->getText()
        );
        $this->assertContains(
            "'client_cookie2' = '123'",
            $this->getSession()->getPage()->getText()
        );
        $this->assertContains(
            "_SESS' = ",
            $this->getSession()->getPage()->getText()
        );
        $this->assertContains(
            " 'srvr_cookie' = 'srv_var_is_set'",
            $this->getSession()->getPage()->getText()
        );

        $this->getSession()->reset();
        $this->getSession()->visit($this->pathTo('/print_cookies.php'));
        $this->assertContains('array ( )', $this->getSession()->getPage()->getText());
    }

    public function testHttpOnlyCookieIsDeleted()
    {
        $this->getSession()->restart();
        $this->getSession()->visit($this->pathTo('/cookie_page3.php'));
        $this->assertEquals('Has Cookie: false', $this->findById('cookie-status')->getText());

        $this->getSession()->reload();
        $this->assertEquals('Has Cookie: true', $this->findById('cookie-status')->getText());

        $this->getSession()->restart();
        $this->getSession()->visit($this->pathTo('/cookie_page3.php'));
        $this->assertEquals('Has Cookie: false', $this->findById('cookie-status')->getText());
    }

    public function testSessionPersistsBetweenRequests()
    {
        $this->getSession()->visit($this->pathTo('/session_test.php'));
        $webAssert = $this->getAssertSession();
        $node = $webAssert->elementExists('css', '#session-id');
        $sessionId = $node->getText();

        $this->getSession()->visit($this->pathTo('/session_test.php'));
        $node = $webAssert->elementExists('css', '#session-id');
        $this->assertEquals($sessionId, $node->getText());

        $this->getSession()->visit($this->pathTo('/session_test.php?login'));
        $node = $webAssert->elementExists('css', '#session-id');
        $this->assertNotEquals($sessionId, $newSessionId = $node->getText());

        $this->getSession()->visit($this->pathTo('/session_test.php'));
        $node = $webAssert->elementExists('css', '#session-id');
        $this->assertEquals($newSessionId, $node->getText());
    }
}
