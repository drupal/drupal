<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests user logout.
 *
 * @group user
 */
class UserLogoutTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->placeBlock('system_menu_block:account');
  }

  /**
   * Tests user logout functionality.
   */
  public function testLogout(): void {
    $account = $this->createUser();
    $this->drupalLogin($account);

    // Test missing csrf token does not log the user out.
    $logoutUrl = Url::fromRoute('user.logout');
    $confirmUrl = Url::fromRoute('user.logout.confirm');
    $this->drupalGet($logoutUrl);
    $this->assertTrue($this->drupalUserIsLoggedIn($account));
    $this->assertSession()->addressEquals($confirmUrl);

    // Test invalid csrf token does not log the user out.
    $this->drupalGet($logoutUrl, ['query' => ['token' => '123']]);
    $this->assertTrue($this->drupalUserIsLoggedIn($account));
    $this->assertSession()->addressEquals($confirmUrl);
    // Test to ensure the text 'This action cannot be undone.' is not
    // present on the page.
    $this->assertSession()->pageTextNotContains('This action cannot be undone.');
    // Submitting the confirmation form correctly logs the user out.
    $this->submitForm([], 'Log out');
    $this->assertFalse($this->drupalUserIsLoggedIn($account));

    $this->drupalResetSession();
    $this->drupalLogin($account);

    // Test with valid logout link.
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');
    $this->assertFalse($this->drupalUserIsLoggedIn($account));

    // Test hitting the confirm form while logged out redirects to the
    // frontpage.
    $this->drupalGet($confirmUrl);
    $this->assertSession()->addressEquals(Url::fromRoute('<front>'));
  }

}
