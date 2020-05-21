<?php

namespace Drupal\Tests\aggregator\Kernel;

use Drupal\aggregator\Entity\Feed;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests feed validation constraints.
 *
 * @group aggregator
 */
class FeedValidationTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['aggregator', 'options'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
    /* @var \Drupal\aggregator\FeedInterface $feed */
    $feed = Feed::create([
      'title' => 'Feed 1',
      'url' => 'https://www.drupal.org/planet/rss.xml',
      'refresh' => 900,
    ]);

    $violations = $feed->validate();

    $this->assertCount(2, $violations);
    $this->assertEqual($violations[0]->getPropertyPath(), 'title');
    $this->assertEqual($violations[0]->getMessage(), t('A feed named %value already exists. Enter a unique title.', [
      '%value' => $feed->label(),
    ]));
    $this->assertEqual($violations[1]->getPropertyPath(), 'url');
    $this->assertEqual($violations[1]->getMessage(), t('A feed with this URL %value already exists. Enter a unique URL.', [
      '%value' => $feed->getUrl(),
    ]));
  }

}
