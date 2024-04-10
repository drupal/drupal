<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Kernel;

use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\announcements_feed\AnnounceFetcher
 *
 * @group announcements_feed
 */
class AnnounceFetcherTest extends AnnounceTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['announcements_feed']);
  }

  /**
   * Tests announcement that should be displayed.
   *
   * @param mixed[] $feed_item
   *   The feed item to test. 'title' and 'url' are omitted from this array
   *   because they do not need to vary between test cases.
   *
   * @dataProvider providerShowAnnouncements
   */
  public function testShowAnnouncements(array $feed_item): void {
    $this->markTestSkipped('Skipped due to major version-specific logic. See https://www.drupal.org/project/drupal/issues/3359322');
    $this->setFeedItems([$feed_item]);
    $feeds = $this->fetchFeedItems();
    $this->assertCount(1, $feeds);
    $this->assertSame('https://www.drupal.org/project/announce', $feeds[0]->url);
    $this->assertSame('Drupal security update Test', $feeds[0]->title);
    $this->assertSame('^10', $feeds[0]->version);
    $this->assertCount(1, $this->history);
  }

  /**
   * Tests feed fields.
   */
  public function testFeedFields(): void {
    $this->markTestSkipped('Skipped due to major version-specific logic. See https://www.drupal.org/project/drupal/issues/3359322');
    $feed_item_1 = [
      'id' => '1001',
      'content_html' => 'Test teaser 1',
      'url' => 'https://www.drupal.org/project/announce',
      '_drupalorg' => [
        'featured' => TRUE,
        'version' => '^10',
      ],
      'date_modified' => "2021-09-02T15:09:42+00:00",
      'date_published' => "2021-09-01T15:09:42+00:00",
    ];
    $this->setFeedItems([$feed_item_1]);
    $feeds = $this->fetchFeedItems();
    $this->assertCount(1, $feeds);
    $this->assertSame($feed_item_1['id'], $feeds[0]->id);
    $this->assertSame($feed_item_1['content_html'], $feeds[0]->content_html);
    $this->assertSame($feed_item_1['_drupalorg']['featured'], $feeds[0]->featured);
    $this->assertSame($feed_item_1['date_published'], $feeds[0]->date_published);
    $this->assertSame($feed_item_1['_drupalorg']['version'], $feeds[0]->version);
  }

  /**
   * Data provider for testShowAnnouncements().
   */
  public static function providerShowAnnouncements(): array {
    return [
      '1' => [
        'feed_item' => [
          'id' => '1001',
          'content_html' => 'Test teaser 1',
          '_drupalorg' => [
            'featured' => 1,
            'version' => '^10',
          ],
          'date_modified' => "2021-09-02T15:09:42+00:00",
          'date_published' => "2021-09-01T15:09:42+00:00",
        ],
      ],
      '2' => [
        'feed_item' => [
          'id' => '1002',
          'content_html' => 'Test teaser 2',
          '_drupalorg' => [
            'featured' => 1,
            'version' => '^10',
          ],
          'date_modified' => "2021-09-02T15:09:42+00:00",
          'date_published' => "2021-09-01T15:09:42+00:00",
        ],
      ],
      '3' => [
        'feed_item' => [
          'id' => '1003',
          'content_html' => 'Test teaser 3',
          '_drupalorg' => [
            'featured' => 1,
            'version' => '^10',
          ],
          'date_modified' => "2021-09-02T15:09:42+00:00",
          'date_published' => "2021-09-01T15:09:42+00:00",
        ],
      ],
      '4' => [
        'feed_item' => [
          'id' => '1004',
          'content_html' => 'Test teaser 4',
          '_drupalorg' => [
            'featured' => 1,
            'version' => '^10',
          ],
          'date_modified' => "2021-09-02T15:09:42+00:00",
          'date_published' => "2021-09-01T15:09:42+00:00",
        ],
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
    $responses = [];
    foreach ($feed_items as $feed_item) {
      $feed_item += [
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
      ];
      $responses[] = new Response(200, [], json_encode(['items' => [$feed_item]]));
    }
    $this->setTestFeedResponses($responses);
  }

  /**
   * Gets the announcements from the 'announce.fetcher' service.
   *
   * @return \Drupal\announcements_feed\Announcement[]
   *   The return value of AnnounceFetcher::fetch().
   */
  protected function fetchFeedItems(): array {
    return $this->container->get('announcements_feed.fetcher')->fetch();
  }

}
