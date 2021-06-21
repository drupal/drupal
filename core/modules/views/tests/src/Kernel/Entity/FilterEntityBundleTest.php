<?php

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the generic entity bundle filter.
 *
 * @group views
 */
class FilterEntityBundleTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_entity_type_filter'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * Tests the generic bundle filter.
   */
  public function testFilterEntity() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    NodeType::create(['type' => 'test_bundle', 'name' => 'Test 1'])->save();
    NodeType::create(['type' => 'test_bundle_2', 'name' => 'Test 2'])->save();
    NodeType::create(['type' => '180575', 'name' => '180575'])->save();

    $bundle_info = $this->container->get('entity_type.bundle.info')->getBundleInfo('node');

    $entities['count'] = 0;

    foreach ($bundle_info as $key => $info) {
      for ($i = 0; $i < 3; $i++) {
        $entity = Node::create([
          'title' => $this->randomString(),
          'uid' => 1,
          'type' => $key,
        ]);
        $entity->save();
        $entities[$key][$entity->id()] = $entity;
        $entities['count']++;
      }
    }
    $view = Views::getView('test_entity_type_filter');

    // Tests \Drupal\views\Plugin\views\filter\Bundle::calculateDependencies().
    $expected = [
      'config' => [
        'node.type.180575',
        'node.type.test_bundle',
        'node.type.test_bundle_2',
      ],
      'module' => [
        'node',
      ],
    ];
    $this->assertSame($expected, $view->getDependencies());

    $this->executeView($view);

    // Test we have all the results, with all types selected.
    $this->assertCount($entities['count'], $view->result);

    // Test the valueOptions of the filter handler.
    $expected = [];
    foreach ($bundle_info as $key => $info) {
      $expected[$key] = $info['label'];
    }
    $this->assertSame($expected, $view->filter['type']->getValueOptions());

    $view->destroy();

    // Test each bundle type.
    foreach ($bundle_info as $key => $info) {
      // Test each bundle type.
      $view->initDisplay();
      $filters = $view->display_handler->getOption('filters');
      $filters['type']['value'] = [$key => $key];
      $view->display_handler->setOption('filters', $filters);
      $this->executeView($view);

      $this->assertSameSize($entities[$key], $view->result);

      $view->destroy();
    }

    // Test an invalid bundle type to make sure we have no results.
    $view->initDisplay();
    $filters = $view->display_handler->getOption('filters');
    $filters['type']['value'] = ['type_3' => 'type_3'];
    $view->display_handler->setOption('filters', $filters);
    $this->executeView($view);

    $this->assertEmpty($view->result);
  }

}
