<?php

namespace Drupal\Tests\system\Functional\Cache;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the 'session.exists' cache context service.
 *
 * @group Cache
 */
class SessionExistsCacheContextTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['session_exists_cache_context_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests \Drupal\Core\Cache\Context\SessionExistsCacheContext::getContext().
   */
  public function testCacheContext() {
    // 1. No session (anonymous).
    $this->assertSessionCookieOnClient(FALSE);
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSessionCookieOnClient(FALSE);
    $this->assertSession()->pageTextContains('Session does not exist!');
    $this->assertSession()->responseContains('[session.exists]=0');

    // 2. Session (authenticated).
    $this->assertSessionCookieOnClient(FALSE);
    $this->drupalLogin($this->rootUser);
    $this->assertSessionCookieOnClient(TRUE);
    $this->assertSession()->pageTextContains('Session exists!');
    $this->assertSession()->responseContains('[session.exists]=1');
    $this->drupalLogout();
    $this->assertSessionCookieOnClient(FALSE);
    $this->assertSession()->pageTextContains('Session does not exist!');
    $this->assertSession()->responseContains('[session.exists]=0');

    // 3. Session (anonymous).
    $this->assertSessionCookieOnClient(FALSE);
    $this->drupalGet(Url::fromRoute('<front>', [], ['query' => ['trigger_session' => 1]]));
    $this->assertSessionCookieOnClient(TRUE);
    $this->assertSession()->pageTextContains('Session does not exist!');
    $this->assertSession()->responseContains('[session.exists]=0');
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSessionCookieOnClient(TRUE);
    $this->assertSession()->pageTextContains('Session exists!');
    $this->assertSession()->responseContains('[session.exists]=1');
  }

  /**
   * Asserts whether a session cookie is present on the client or not.
   *
   * @internal
   */
  public function assertSessionCookieOnClient(bool $expected_present): void {
    $this->assertEquals($expected_present, (bool) $this->getSession()->getCookie($this->getSessionName()), 'Session cookie exists.');
  }

}
