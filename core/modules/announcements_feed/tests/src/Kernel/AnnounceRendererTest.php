<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Kernel;

use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\announcements_feed\AnnounceRenderer
 *
 * @group announcements_feed
 */
class AnnounceRendererTest extends AnnounceTestBase {

  /**
   * Tests rendered valid when something goes wrong.
   */
  public function testRendererException() {
    $this->setTestFeedResponses([
      new Response(403),
    ]);
    $render = $this->container->get('announcements_feed.renderer')->render();
    $this->assertEquals('status_messages', $render['#theme']);
    $this->assertEquals('An error occurred while parsing the announcements feed, check the logs for more information.', $render['#message_list']['error'][0]);
  }

  /**
   * Tests rendered valid content.
   */
  public function testRendererContent() {
    $feed_item_1 = [
      'id' => '1001',
      'content_html' => 'Test teaser 1',
      'url' => 'https://www.drupal.org/project/announce',
      '_drupalorg' => [
        'featured' => TRUE,
        'version' => '^10||^11',
      ],
      'date_modified' => "2021-09-02T15:09:42+00:00",
      'date_published' => "2021-09-01T15:09:42+00:00",
    ];
    $feed_item_2 = [
      'id' => '1002',
      'content_html' => 'Test teaser 1',
      'url' => 'https://www.drupal.org/project/announce',
      '_drupalorg' => [
        'featured' => FALSE,
        'version' => '^10||^11',
      ],
      'date_modified' => "2021-09-02T15:09:42+00:00",
      'date_published' => "2021-09-01T15:09:42+00:00",
    ];
    $this->setFeedItems([$feed_item_1, $feed_item_2]);
    $render = $this->container->get('announcements_feed.renderer')->render();
    $this->assertEquals('announcements_feed', $render['#theme']);
    $this->assertEquals(1, $render['#count']);
    $this->assertEquals(1001, $render['#featured'][0]->id);

    $render = $this->container->get('announcements_feed.renderer')->render();
    $this->assertEquals('announcements_feed', $render['#theme']);
    $this->assertEquals(1, $render['#count']);
    $this->assertEquals(1002, $render['#standard'][0]->id);
  }

}
