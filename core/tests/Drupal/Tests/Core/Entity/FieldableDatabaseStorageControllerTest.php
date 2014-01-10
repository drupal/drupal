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

    // Mock the entity manager to return the minimal entity and field
    // definitions for the test_entity entity.
    $definition = new EntityType(array(
      'entity_keys' => array(
        'id' => 'id',
        'revision_id' => 'revision_id',
      ),
    ));
    $fields['id'] = FieldDefinition::create('string')
      ->setName('id');
    $fields['revision_id'] = FieldDefinition::create('string')
      ->setName('revision_id');

    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity')
      ->will($this->returnValue($definition));

    $entity_manager->expects($this->any())
      ->method('getFieldDefinitions')
      ->with('test_entity')
      ->will($this->returnValue($fields));

    $container = new ContainerBuilder();
    $container->set('entity.manager', $entity_manager);
    \Drupal::setContainer($container);

    // Define a field definition for a test_field field.
    $field = $this->getMock('\Drupal\field\FieldInterface');
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
