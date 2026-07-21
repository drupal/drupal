<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Functional;

use Drupal\announce_feed_test\AnnounceTestHttpClientMiddleware;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the access announcement according to json feed changes.
 */
#[Group('announcements_feed')]
#[RunTestsInSeparateProcesses]
class AlertsJsonFeedTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'announcements_feed',
    'announce_feed_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to access toolbar and access announcements.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();

    $this->user = $this->drupalCreateUser(
      [
        'access announcements',
      ]
    );

    AnnounceTestHttpClientMiddleware::setAnnounceTestEndpoint('/announce-feed-json/community-feeds');

    // Change the version constraint in the updated json feed.
    $contents = file_get_contents(dirname(__DIR__, 2) . '/announce_feed/updated.json');
    $version = explode('.', \Drupal::VERSION, 2);
    $constraint = "^$version[0]";
    $new_contents = str_replace("^10", $constraint, $contents);
    file_put_contents($this->publicFilesDirectory . '/updated.json', $new_contents);
  }

  /**
   * Check the status of the announcements when the feed is updated and removed.
   */
  public function testAnnounceFeedUpdatedAndRemoved(): void {
    $this->drupalLogin($this->user);
    $this->drupalGet('/admin/announcements_feed');
    $page_html = $this->getSession()->getPage()->getHtml();
    $this->assertStringNotContainsString('Only 10 - Drupal 106 is available and this feed is Updated', $page_html);

    // Change the feed url and reset temp storage.
    AnnounceTestHttpClientMiddleware::setAnnounceTestEndpoint('/announce-feed-json/updated');

    $this->drupalGet('/admin/announcements_feed');
    $page_html = $this->getSession()->getPage()->getHtml();
    $this->assertStringContainsString('Only 10 - Drupal 106 is available and this feed is Updated', $page_html);
    $this->drupalLogout();

    // Change the feed url and reset temp storage.
    AnnounceTestHttpClientMiddleware::setAnnounceTestEndpoint('/announce-feed-json/removed');
    $this->drupalGet('/admin/announcements_feed');
    $page_html = $this->getSession()->getPage()->getHtml();
    $this->assertStringNotContainsString('Only 10 - Drupal 106 is available and this feed is Updated', $page_html);
  }

  /**
   * Check with an empty JSON feed.
   */
  public function testAnnounceFeedEmpty(): void {
    // Change the feed url and reset temp storage.
    AnnounceTestHttpClientMiddleware::setAnnounceTestEndpoint('/announce-feed-json/empty');

    $this->drupalLogin($this->user);
    $this->drupalGet('/admin/announcements_feed');
    $this->assertStringContainsString('No announcements available', $this->getSession()->getPage()->getHtml());
  }

}
