<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Views\IntegrationTest.
 */

namespace Drupal\aggregator\Tests\Views;

use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests basic views integration of aggregator module.
 */
class IntegrationTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('aggregator', 'aggregator_test_views', 'system', 'entity', 'field', 'options');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_aggregator_items');

  /**
   * The entity storage for aggregator items.
   *
   * @var \Drupal\aggregator\ItemStorage
   */
  protected $itemStorage;

  /**
   * The entity storage for aggregator feeds.
   *
   * @var \Drupal\aggregator\FeedStorage
   */
  protected $feedStorage;

  public static function getInfo() {
    return array(
      'name' => 'Aggregator: Integration tests',
      'description' => 'Tests basic integration of views data from the aggregator module.',
      'group' => 'Views module integration',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('aggregator_item');
    $this->installEntitySchema('aggregator_feed');

    ViewTestData::createTestViews(get_class($this), array('aggregator_test_views'));

    $this->itemStorage = $this->container->get('entity.manager')->getStorage('aggregator_item');
    $this->feedStorage = $this->container->get('entity.manager')->getStorage('aggregator_feed');
  }

  /**
   * Tests basic aggregator_item view.
   */
  public function testAggregatorItemView() {
    $items = array();
    $expected = array();
    for ($i = 0; $i < 10; $i++) {
      $values = array();
      $values['timestamp'] = mt_rand(REQUEST_TIME - 10, REQUEST_TIME + 10);
      $values['title'] = $this->randomName();
      $values['description'] = $this->randomName();
      // Add a image to ensure that the sanitizing can be tested below.
      $values['author'] = $this->randomName() . '<img src="http://example.com/example.png" \>"';
      $values['link'] = 'http://drupal.org/node/' . mt_rand(1000, 10000);
      $values['guid'] = $this->randomString();

      $aggregator_item = $this->itemStorage->create($values);
      $aggregator_item->save();
      $items[$aggregator_item->id()] = $aggregator_item;

      $values['iid'] = $aggregator_item->id();
      $expected[] = $values;
    }

    $view = Views::getView('test_aggregator_items');
    $this->executeView($view);

    $column_map = array(
      'iid' => 'iid',
      'aggregator_item_title' => 'title',
      'aggregator_item_timestamp' => 'timestamp',
      'aggregator_item_description' => 'description',
      'aggregator_item_author' => 'author',
    );
    $this->assertIdenticalResultset($view, $expected, $column_map);

    // Ensure that the rendering of the linked title works as expected.
    foreach ($view->result as $row) {
      $iid = $view->field['iid']->getValue($row);
      $expected_link = l($items[$iid]->getTitle(), $items[$iid]->getLink(), array('absolute' => TRUE));
      $this->assertEqual($view->field['title']->advancedRender($row), $expected_link, 'Ensure the right link is generated');

      $expected_author = aggregator_filter_xss($items[$iid]->getAuthor());
      $this->assertEqual($view->field['author']->advancedRender($row), $expected_author, 'Ensure the author got filtered');

      $expected_description = aggregator_filter_xss($items[$iid]->getDescription());
      $this->assertEqual($view->field['description']->advancedRender($row), $expected_description, 'Ensure the author got filtered');
    }
  }

}
