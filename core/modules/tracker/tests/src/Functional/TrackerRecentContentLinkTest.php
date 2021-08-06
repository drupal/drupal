<?php

namespace Drupal\Tests\tracker\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests recent content link.
 *
 * @group tracker
 */
class TrackerRecentContentLinkTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'tracker'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the recent content link in menu block.
   */
  public function testRecentContentLink() {
    $this->drupalGet('<front>');
    $this->assertSession()->linkNotExists('Recent content');
    $this->drupalPlaceBlock('system_menu_block:tools');

    // Create a regular user.
    $user = $this->drupalCreateUser();

    // Log in and get the homepage.
    $this->drupalLogin($user);
    $this->drupalGet('<front>');
    $this->assertSession()->elementsCount('xpath', '//ul/li/a[contains(@href, "/activity") and text()="Recent content"]', 1);
  }

}
