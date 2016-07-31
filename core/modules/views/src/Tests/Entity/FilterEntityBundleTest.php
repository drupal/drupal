<?php

namespace Drupal\views\Tests\Entity;

use Drupal\node\Entity\Node;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the generic entity bundle filter.
 *
 * @group views
 */
class FilterEntityBundleTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_type_filter');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Entity bundle data.
   *
   * @var array
   */
  protected $entityBundles;

  /**
   * An array of entities.
   *
   * @var array
   */
  protected $entities = array();

  protected function setUp() {
    parent::setUp(FALSE);

    $this->drupalCreateContentType(array('type' => 'test_bundle'));
    $this->drupalCreateContentType(array('type' => 'test_bundle_2'));

    ViewTestData::createTestViews(get_class($this), array('views_test_config'));

    $this->entityBundles = $this->container->get('entity_type.bundle.info')->getBundleInfo('node');

    $this->entities['count'] = 0;

    foreach ($this->entityBundles as $key => $info) {
      for ($i = 0; $i < 5; $i++) {
        $entity = Node::create([
          'title' => $this->randomString(),
          'uid' => 1,
          'type' => $key,
        ]);
        $entity->save();
        $this->entities[$key][$entity->id()] = $entity;
        $this->entities['count']++;
      }
    }
  }

  /**
   * Tests the generic bundle filter.
   */
  public function testFilterEntity() {
    $view = Views::getView('test_entity_type_filter');

    // Tests \Drupal\views\Plugin\views\filter\Bundle::calculateDependencies().
    $expected = [
      'config' => [
        'node.type.test_bundle',
        'node.type.test_bundle_2',
      ],
      'module' => [
        'node'
      ],
    ];
    $this->assertIdentical($expected, $view->getDependencies());

    $this->executeView($view);

    // Test we have all the results, with all types selected.
    $this->assertEqual(count($view->result), $this->entities['count']);

    // Test the valueOptions of the filter handler.
    $expected = array();

    foreach ($this->entityBundles as $key => $info) {
      $expected[$key] = $info['label'];
    }
    $this->assertIdentical($view->filter['type']->getValueOptions(), $expected);

    $view->destroy();

    // Test each bundle type.
    foreach ($this->entityBundles as $key => $info) {
      // Test each bundle type.
      $view->initDisplay();
      $filters = $view->display_handler->getOption('filters');
      $filters['type']['value'] = array($key => $key);
      $view->display_handler->setOption('filters', $filters);
      $this->executeView($view);

      $this->assertEqual(count($view->result), count($this->entities[$key]));

      $view->destroy();
    }

    // Test an invalid bundle type to make sure we have no results.
    $view->initDisplay();
    $filters = $view->display_handler->getOption('filters');
    $filters['type']['value'] = array('type_3' => 'type_3');
    $view->display_handler->setOption('filters', $filters);
    $this->executeView($view);

    $this->assertEqual(count($view->result), 0);
  }

}
