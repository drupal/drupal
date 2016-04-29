<?php

namespace Drupal\Tests\aggregator\Kernel;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;
use Drupal\KernelTests\KernelTestBase;


/**
 * Tests the aggregator_title formatter.
 *
 * @group field
 */
class AggregatorTitleTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'field', 'options', 'aggregator', 'system'];

  /**
   * The field name that is tested.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['field']);
    $this->installEntitySchema('aggregator_feed');
    $this->installEntitySchema('aggregator_item');

    \Drupal::service('router.builder')->rebuild();

    $this->fieldName = 'title';
  }

  /*
   * Tests the formatter output.
   */
  public function testStringFormatter() {
    // Create an aggregator feed.
    $aggregator_feed = Feed::create([
      'title' => 'testing title',
      'url' => 'http://www.example.com',
    ]);
    $aggregator_feed->save();

    // Create an aggregator feed item.
    $aggregator_item = Item::create([
      'title' => 'test title',
      'fid' => $aggregator_feed->id(),
      'link' => 'http://www.example.com',
      ]);
    $aggregator_item->save();

    // Verify aggregator feed title with and without links.
    $build = $aggregator_feed->{$this->fieldName}->view(['type' => 'aggregator_title', 'settings' => ['display_as_link' => TRUE]]);
    $result = $this->render($build);

    $this->assertContains('testing title', $result);
    $this->assertContains('href="' . $aggregator_feed->getUrl() . '"', $result);

    $build = $aggregator_feed->{$this->fieldName}->view(['type' => 'aggregator_title', 'settings' => ['display_as_link' => FALSE]]);
    $result = $this->render($build);
    $this->assertContains('testing title', $result);
    $this->assertNotContains($aggregator_feed->getUrl(), $result);

    // Verify aggregator item title with and without links.
    $build = $aggregator_item->{$this->fieldName}->view(['type' => 'aggregator_title', 'settings' => ['display_as_link' => TRUE]]);
    $result = $this->render($build);

    $this->assertContains('test title', $result);
    $this->assertContains('href="' . $aggregator_item->getLink() . '"', $result);

    $build = $aggregator_item->{$this->fieldName}->view(['type' => 'aggregator_title', 'settings' => ['display_as_link' => FALSE]]);
    $result = $this->render($build);
    $this->assertContains('test title', $result);
    $this->assertNotContains($aggregator_item->getLink(), $result);
  }

}
