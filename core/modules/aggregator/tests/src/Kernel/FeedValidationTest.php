<?php

namespace Drupal\Tests\aggregator\Kernel;

use Drupal\aggregator\Entity\Feed;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests feed validation constraints.
 *
 * @group aggregator
 * @group legacy
 */
class FeedValidationTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['aggregator', 'options'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');
  }

  /**
   * Tests the feed validation constraints.
   */
  public function testValidation() {
    // Add feed.
    $feed = Feed::create([
      'title' => 'Feed 1',
      'url' => 'https://www.drupal.org/planet/rss.xml',
      'refresh' => 900,
    ]);

    $violations = $feed->validate();
    $this->assertCount(0, $violations);

    $feed->save();

    // Add another feed.
    /** @var \Drupal\aggregator\FeedInterface $feed */
    $feed = Feed::create([
      'title' => 'Feed 1',
      'url' => 'https://www.drupal.org/planet/rss.xml',
      'refresh' => 900,
    ]);

    $violations = $feed->validate();

    $this->assertCount(2, $violations);
    $this->assertEquals('title', $violations[0]->getPropertyPath());
    $this->assertEquals(t('A feed named %value already exists. Enter a unique title.', ['%value' => $feed->label()]), $violations[0]->getMessage());
    $this->assertEquals('url', $violations[1]->getPropertyPath());
    $this->assertEquals(t('A feed with this URL %value already exists. Enter a unique URL.', ['%value' => $feed->getUrl()]), $violations[1]->getMessage());
  }

}
