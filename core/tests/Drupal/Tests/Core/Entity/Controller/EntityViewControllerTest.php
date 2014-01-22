<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Controller\EntityViewControllerTest.
 */

namespace Drupal\Tests\Core\Entity\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the entity view controller.
 *
 * @group Drupal
 * @group Entity
 *
 * @see \Drupal\Core\Entity\Controller\EntityViewController
 */
class EntityViewControllerTest extends UnitTestCase{

  public static function getInfo() {
    return array(
      'name' => 'Entity view controller test',
      'description' => 'Tests the entity view controller.',
      'group' => 'Entity'
    );
  }

  /**
   * Tests the enhancer method.
   *
   * @see \Drupal\Core\Entity\Controller\EntityViewController::view()
   */
  public function testView() {

    // Mock a view builder.
    $render_controller = $this->getMockBuilder('Drupal\entity_test\EntityTestViewBuilder')
      ->disableOriginalConstructor()
      ->getMock();
    $render_controller->expects($this->any())
      ->method('view')
      ->will($this->returnValue('Output from rendering the entity'));

    // Mock an entity manager.
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getViewBuilder')
      ->will($this->returnValue($render_controller));

    // Mock an 'entity_test' entity.
    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();

    // Initialize the controller to test.
    $controller = new EntityViewController($entity_manager);

    // Test the view method.
    $this->assertEquals($controller->view($entity, 'full'), 'Output from rendering the entity');
  }
}
