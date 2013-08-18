<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Controller\EntityViewControllerTest.
 */

namespace Drupal\Core\Tests\Entity\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the entity view controller.
 *
 * @see \Drupal\Core\Entity\Controller\EntityViewController
 */
class EntityViewControllerTest extends UnitTestCase{

  public static function getInfo() {
    return array(
      'name' => 'Entity route enhancer test',
      'description' => 'Tests the entity route enhancer.',
      'group' => 'Entity'
    );
  }

  /**
   * Tests the enhancer method.
   *
   * @see \Drupal\Core\Entity\Controller\EntityViewController::view()
   */
  public function testView() {

    // Mock a render controller.
    $render_controller = $this->getMockBuilder('Drupal\entity_test\EntityTestRenderController')
      ->disableOriginalConstructor()
      ->getMock();
    $render_controller->expects($this->any())
      ->method('view')
      ->will($this->returnValue('Output from rendering the entity'));

    // Mock an entity manager.
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_manager->expects($this->any())
      ->method('getRenderController')
      ->will($this->returnValue($render_controller));

    // Mock an 'entity_test_render' entity.
    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTestRender')
      ->disableOriginalConstructor()
      ->getMock();

    // Initialize the controller to test.
    $controller = new EntityViewController($entity_manager);

    // Test the view method.
    $this->assertEquals($controller->view($entity, 'full'), 'Output from rendering the entity');
  }
}
