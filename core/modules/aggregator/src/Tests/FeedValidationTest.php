<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\FeedValidationTest.
 */

namespace Drupal\aggregator\Tests;

use Drupal\aggregator\Entity\Feed;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests feed validation constraints.
 *
 * @group aggregator
 */
class FeedValidationTest extends EntityUnitTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('aggregator', 'options');

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
      'url' => 'http://drupal.org/planet/rss',
      'refresh' => 900,
    ]);

    $violations = $feed->validate();
    $this->assertEqual(count($violations), 0);

    $feed->save();

    // Add another feed.
    /* @var \Drupal\aggregator\FeedInterface $feed */
    $feed = Feed::create([
      'title' => 'Feed 1',
      'url' => 'http://drupal.org/planet/rss',
      'refresh' => 900,
    ]);

    $violations = $feed->validate();

    $this->assertEqual(count($violations), 2);
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
