<?php

namespace Drupal\Tests\announcements_feed\FunctionalJavascript;

use Drupal\Tests\system\FunctionalJavascript\OffCanvasTestBase;
use Drupal\announce_feed_test\AnnounceTestHttpClientMiddleware;

/**
 * Test the access announcement permissions to get access announcement icon.
 *
 * @group announcements_feed
 */
class AccessAnnouncementTest extends OffCanvasTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'toolbar',
    'announcements_feed',
    'announce_feed_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    AnnounceTestHttpClientMiddleware::setAnnounceTestEndpoint('/announce-feed-json/community-feeds');
  }

  /**
   * Test of viewing announcements by a user with appropriate permission.
   */
  public function testAnnounceFirstLogin() {
    $this->drupalLogin(
      $this->drupalCreateUser(
        [
          'access toolbar',
          'access announcements',
        ]
      )
    );

    $this->drupalGet('<front>');

    // Check that the user can see the toolbar.
    $this->assertSession()->elementExists('css', '#toolbar-bar');

    // And the announcements.
    $this->assertSession()->elementExists('css', '.toolbar-icon-announce');
  }

  /**
   * Testing announce icon without announce permission.
   */
  public function testAnnounceWithoutPermission() {
    // User without "access announcements" permission.
    $account = $this->drupalCreateUser(
      [
        'access toolbar',
      ]
    );
    $this->drupalLogin($account);
    $this->drupalGet('<front>');

    // Check that the user can see the toolbar.
    $this->assertSession()->elementExists('css', '#toolbar-bar');

    // But not the announcements.
    $this->assertSession()->elementNotExists('css', '.toolbar-icon-announce');

    $this->drupalGet('admin/announcements_feed');
    $this->assertSession()->responseContains('You are not authorized to access this page.');
  }

}
