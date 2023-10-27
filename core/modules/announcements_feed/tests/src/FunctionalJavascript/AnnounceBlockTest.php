<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\FunctionalJavascript;

use Drupal\announce_feed_test\AnnounceTestHttpClientMiddleware;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the announcement block test visibility.
 *
 * @group announcements_feed
 */
class AnnounceBlockTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'announcements_feed',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The announce block instance.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $announceBlock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    AnnounceTestHttpClientMiddleware::setAnnounceTestEndpoint('/announce-feed-json/community-feeds');
    $this->announceBlock = $this->placeBlock('announce_block', [
      'label' => 'Announcements Feed',
    ]);
  }

  /**
   * Testing announce feed block visibility.
   */
  public function testAnnounceWithoutPermission() {
    // User with "access announcements" permission.
    $account = $this->drupalCreateUser([
      'access announcements',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('<front>');

    $assert_session = $this->assertSession();

    // Block should be visible for the user.
    $assert_session->pageTextContains('Announcements Feed');

    // Block is not accessible without permission.
    $this->drupalLogout();
    $assert_session->pageTextNotContains('Announcements Feed');

  }

}
