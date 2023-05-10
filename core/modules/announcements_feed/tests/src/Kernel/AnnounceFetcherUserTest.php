<?php

namespace Drupal\Tests\announcements_feed\Kernel;

use Drupal\Tests\user\Traits\UserCreationTrait;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\announcements_feed\AnnounceFetcher
 *
 * @group announcements_feed
 */
class AnnounceFetcherUserTest extends AnnounceTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('user', ['users_data']);

    // Setting current user.
    $permissions = [
      'access toolbar',
      'access announcements',
    ];
    $this->setUpCurrentUser(['uid' => 1], $permissions);
  }

  /**
   * Tests testAllAnnouncements should get all announcements.
   *
   * First time accessing the announcements.
   */
  public function testAllAnnouncementsFirst(): void {
    $this->markTestSkipped('Skipped due to major version-specific logic. See https://www.drupal.org/project/drupal/issues/3359322');

    $feed_items = $this->providerShowAnnouncements();

    // First time access.
    $this->setFeedItems($feed_items);
    $all_items = $this->container->get('announcements_feed.fetcher')->fetch();
    $this->assertCount(4, $all_items);
    $this->assertCount(1, $this->history);

    // Second time access.
    $this->setFeedItems($feed_items);
    $all_items = $this->container->get('announcements_feed.fetcher')->fetch();
    $this->assertCount(4, $all_items);
    $this->assertCount(2, $this->history);

    // Create another user and test again.
    $permissions = [
      'access toolbar',
      'access announcements',
    ];
    $this->setUpCurrentUser(['uid' => 2], $permissions);
    $this->setFeedItems($feed_items);

    // First time access.
    $all_items = $this->container->get('announcements_feed.fetcher')->fetch();
    $this->assertCount(4, $all_items);
    $this->assertCount(3, $this->history);

    // Check after adding new record.
    $feed_items = $this->providerShowUpdatedAnnouncements();
    $this->setFeedItems($feed_items);
    $all_items = $this->container->get('announcements_feed.fetcher')->fetch();
    $this->assertCount(5, $all_items);
    $this->assertSame('1005', $all_items[0]->id);
    $this->assertCount(4, $this->history);
  }

  /**
   * Data provider for testAllAnnouncements().
   */
  public function providerShowAnnouncements(): array {
    return [
      [
        'id' => '1001',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 1',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [
        'id' => '1002',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 2',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [

        'id' => '1003',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 3',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [
        'id' => '1004',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 4',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
    ];
  }

  /**
   * Data provider for testAllAnnouncements().
   */
  public function providerShowUpdatedAnnouncements(): array {
    return [

      [
        'id' => '1005',
        'title' => 'Drupal security update Test new',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 1',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [
        'id' => '1001',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 1',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [
        'id' => '1002',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 2',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [

        'id' => '1003',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 3',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
      [
        'id' => '1004',
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
        'content_html' => 'Test teaser 4',
        '_drupalorg' => [
          'featured' => TRUE,
          'version' => '^10',
        ],
        'date_modified' => date('c', 1611041378),
        'date_published' => date('c', 1610958578),
      ],
    ];
  }

  /**
   * Sets the feed items to be returned for the test.
   *
   * @param mixed[][] $feed_items
   *   The feeds items to test. Every time the http_client makes a request the
   *   next item in this array will be returned. For each feed item 'title' and
   *   'url' are omitted because they do not need to vary between test cases.
   */
  protected function setFeedItems(array $feed_items): void {
    $responses[] = new Response(200, [], json_encode(['items' => $feed_items]));
    $responses[] = new Response(200, [], json_encode(['items' => $feed_items]));
    $responses[] = new Response(200, [], json_encode(['items' => $feed_items]));

    $this->setTestFeedResponses($responses);
  }

}
