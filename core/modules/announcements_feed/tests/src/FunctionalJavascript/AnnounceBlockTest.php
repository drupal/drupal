<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\FunctionalJavascript;

use Drupal\announce_feed_test\AnnounceTestHttpClientMiddleware;
use Drupal\block\BlockInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Session\AnonymousUserSession;
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
   * @var \Drupal\block\BlockInterface
   */
  protected BlockInterface $announceBlock;

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
  public function testAnnounceWithoutPermission(): void {
    // User with "access announcements" permission and anonymous session.
    $account = $this->drupalCreateUser([
      'access announcements',
    ]);
    $anonymous_account = new AnonymousUserSession();

    $this->drupalLogin($account);
    $this->drupalGet('<front>');

    $assert_session = $this->assertSession();

    // Block should be visible for the user.
    $assert_session->pageTextContains('Announcements Feed');

    // Block is not accessible without permission.
    $this->drupalLogout();
    $assert_session->pageTextNotContains('Announcements Feed');

    // Test access() method return type.
    $this->assertTrue($this->announceBlock->getPlugin()->access($account));
    $this->assertInstanceOf(AccessResultAllowed::class, $this->announceBlock->getPlugin()->access($account, TRUE));

    $this->assertFalse($this->announceBlock->getPlugin()->access($anonymous_account));
    $this->assertInstanceOf(AccessResultNeutral::class, $this->announceBlock->getPlugin()->access($anonymous_account, TRUE));
  }

}
