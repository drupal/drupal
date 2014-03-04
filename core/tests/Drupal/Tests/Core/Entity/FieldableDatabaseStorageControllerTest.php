<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\FieldableDatabaseStorageControllerTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Entity\FieldableDatabaseStorageController;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the fieldable database storage controller.
 *
 * @see \Drupal\Core\Entity\FieldableDatabaseStorageController
 *
 * @group Drupal
 * @group Entity
 */
class FieldableDatabaseStorageControllerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Fieldable database storage controller',
      'description' => 'Tests the fieldable database storage enhancer for entities.',
      'group' => 'Entity'
    );
  }

  /**
   * Tests field SQL schema generation for an entity with a string identifier.
   *
   * @see \Drupal\Core\Entity\Controller\FieldableDatabaseStorageController::_fieldSqlSchema()
   */
  public function testFieldSqlSchemaForEntityWithStringIdentifier() {
    $field_type_manager = $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface');
    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    $container->set('entity.manager', $entity_manager);
    \Drupal::setContainer($container);

    $definition = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $definition->expects($this->any())
      ->method('getKey')
      ->will($this->returnValueMap(array(
        array('id', 'id'),
        array('revision_id', 'revision_id'),
      )));
    $definition->expects($this->once())
      ->method('hasKey')
      ->with('revision_id')
      ->will($this->returnValue(TRUE));

    $field_type_manager->expects($this->exactly(2))
      ->method('getDefaultSettings')
      ->will($this->returnValue(array()));
    $field_type_manager->expects($this->exactly(2))
      ->method('getDefaultInstanceSettings')
      ->will($this->returnValue(array()));

    $fields['id'] = FieldDefinition::create('string')
      ->setName('id');
    $fields['revision_id'] = FieldDefinition::create('string')
      ->setName('revision_id');

    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity')
      ->will($this->returnValue($definition));
    $entity_manager->expects($this->any())
      ->method('getBaseFieldDefinitions')
      ->with('test_entity')
      ->will($this->returnValue($fields));

    // Define a field definition for a test_field field.
    $field = $this->getMock('\Drupal\field\FieldConfigInterface');
    $field->deleted = FALSE;
    $field->entity_type = 'test_entity';
    $field->name = 'test_field';

    $field->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('test'));

    $field_schema = array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(),
      'foreign keys' => array(),
    );
    $field->expects($this->any())
      ->method('getSchema')
      ->will($this->returnValue($field_schema));

    $schema = FieldableDatabaseStorageController::_fieldSqlSchema($field);

    // Make sure that the entity_id schema field if of type varchar.
    $this->assertEquals($schema['test_entity__test_field']['fields']['entity_id']['type'], 'varchar');
    $this->assertEquals($schema['test_entity__test_field']['fields']['revision_id']['type'], 'varchar');
  }

}
