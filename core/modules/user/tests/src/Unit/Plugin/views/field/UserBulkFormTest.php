<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Plugin\views\field\UserBulkFormTest.
 */

namespace Drupal\Tests\user\Unit\Plugin\views\field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\views\field\UserBulkForm;

/**
 * @coversDefaultClass \Drupal\user\Plugin\views\field\UserBulkForm
 * @group user
 */
class UserBulkFormTest extends UnitTestCase {

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
        ->will($this->returnValue('user'));
      $actions[$i] = $action;
    }

    $action = $this->getMock('\Drupal\system\ActionConfigEntityInterface');
    $action->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('node'));
    $actions[] = $action;

    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue($actions));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('action')
      ->will($this->returnValue($entity_storage));

    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->any())
      ->method('get')
      ->with('users')
      ->will($this->returnValue(array('table' => array('entity type' => 'user'))));
    $container = new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $storage = $this->getMock('Drupal\views\ViewEntityInterface');
    $storage->expects($this->any())
      ->method('get')
      ->with('base_table')
      ->will($this->returnValue('users'));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->storage = $storage;

    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $definition['title'] = '';
    $options = array();

    $user_bulk_form = new UserBulkForm(array(), 'user_bulk_form', $definition, $entity_manager);
    $user_bulk_form->init($executable, $display, $options);

    $this->assertAttributeEquals(array_slice($actions, 0, -1, TRUE), 'actions', $user_bulk_form);
  }

}
