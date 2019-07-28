<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\argument\StringArgument handler.
 *
 * @group views
 */
class ArgumentStringTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_glossary'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
  ];

  /**
   * Tests the glossary feature.
   */
  public function testGlossary() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    NodeType::create(['type' => 'page'])->save();

    // Setup some nodes, one with a, two with b and three with c.
    $counter = 1;
    foreach (['a', 'b', 'c'] as $char) {
      for ($i = 0; $i < $counter; $i++) {
        Node::create([
          'type' => 'page',
          'title' => $char . $this->randomMachineName(),
        ])->save();
      }
    }

    $view = Views::getView('test_glossary');
    $this->executeView($view);

    $count_field = 'nid';
    foreach ($view->result as &$row) {
      if (strpos($view->field['title']->getValue($row), 'a') === 0) {
        $this->assertEquals(1, $row->{$count_field});
      }
      if (strpos($view->field['title']->getValue($row), 'b') === 0) {
        $this->assertEquals(2, $row->{$count_field});
      }
      if (strpos($view->field['title']->getValue($row), 'c') === 0) {
        $this->assertEquals(3, $row->{$count_field});
      }
    }
  }

}
