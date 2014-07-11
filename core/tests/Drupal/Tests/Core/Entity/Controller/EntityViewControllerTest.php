<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\Controller\EntityViewControllerTest.
 */

namespace Drupal\Tests\Core\Entity\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Controller\EntityViewController
 * @group Entity
 */
class EntityViewControllerTest extends UnitTestCase{

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

    // Mock the 'entity_test' entity type.
    $entity_type = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityType')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('label')
      ->will($this->returnValue('name'));

    // Mock the 'name' field's definition.
    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinition');
    $field_definition->expects($this->any())
      ->method('getDisplayOptions')
      ->with('view')
      ->will($this->returnValue(NULL));

    // Mock an 'entity_test' entity.
    $entity = $this->getMockBuilder('Drupal\entity_test\Entity\EntityTest')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->once())
      ->method('getEntityType')
      ->will($this->returnValue($entity_type));
    $entity->expects($this->any())
      ->method('getFieldDefinition')
      ->with('name')
      ->will($this->returnValue($field_definition));


    // Initialize the controller to test.
    $controller = new EntityViewController($entity_manager);

    // Test the view method.
    $this->assertEquals($controller->view($entity, 'full'), 'Output from rendering the entity');
  }
}
