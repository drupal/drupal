<?php

/**
 * @file
 * Contains \Drupal\Tests\node\Unit\Plugin\views\field\NodeBulkFormTest.
 */

namespace Drupal\Tests\node\Unit\Plugin\views\field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\node\Plugin\views\field\NodeBulkForm;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\node\Plugin\views\field\NodeBulkForm
 * @group node
 */
class NodeBulkFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Tests the constructor assignment of actions.
   */
  public function testConstructor() {
    $actions = array();

    for ($i = 1; $i <= 2; $i++) {
      $action = $this->getMock('\Drupal\system\ActionConfigEntityInterface');
      $action->expects($this->any())
        ->method('getType')
        ->will($this->returnValue('node'));
      $actions[$i] = $action;
    }

    $action = $this->getMock('\Drupal\system\ActionConfigEntityInterface');
    $action->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('user'));
    $actions[] = $action;

    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue($actions));

    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->any())
      ->method('get')
      ->with('node')
      ->will($this->returnValue(array('table' => array('entity type' => 'node'))));
    $container = new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    \Drupal::setContainer($container);

    $storage = $this->getMock('Drupal\views\ViewStorageInterface');
    $storage->expects($this->any())
      ->method('get')
      ->with('base_table')
      ->will($this->returnValue('node'));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->storage = $storage;

    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $definition['title'] = '';
    $options = array();

    $node_bulk_form = new NodeBulkForm(array(), 'node_bulk_form', $definition, $entity_storage);
    $node_bulk_form->init($executable, $display, $options);

    $this->assertAttributeEquals(array_slice($actions, 0, -1, TRUE), 'actions', $node_bulk_form);
  }

}
