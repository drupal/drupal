<?php

/**
 * @file
 * Contains \Drupal\field\Tests\ConfigFieldDefinitionTest.
 */

namespace Drupal\field\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests exposing field definitions for configurable fields.
 */
class ConfigFieldDefinitionTest extends FieldUnitTestBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface;
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Config field definitions',
      'description' => 'Tests exposing field definitions for configurable fields.',
      'group' => 'Field API',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a field and instance of type 'test_field', on the 'entity_test'
    // entity type.
    $this->entityType = 'entity_test';
    $this->bundle = 'entity_test';
    $this->createFieldWithInstance('', $this->entityType, $this->bundle);
    $this->entityManager = $this->container->get('entity.manager');

    // Create a second instance on 'entity_test_rev'.
    $this->createFieldWithInstance('_rev', 'entity_test_rev', 'entity_test_rev');
  }

  /**
   * Makes sure a field definition is exposed for a configurable field.
   */
  public function testBundleFieldDefinition() {
    $definitions = $this->entityManager->getFieldDefinitions($this->entityType, $this->bundle);
    $this->assertTrue(isset($definitions[$this->instance->getName()]));
    $this->assertTrue($definitions[$this->instance->getName()] instanceof FieldStorageDefinitionInterface);
    // Make sure no field for the instance on another entity type is exposed.
    $this->assertFalse(isset($definitions[$this->instance_rev->getName()]));
  }

  /**
   * Makes sure a field storage definition is exposed for a configurable field.
   */
  public function testFieldStorageDefinition() {
    $field_storage_definitions = $this->entityManager->getFieldStorageDefinitions($this->entityType);
    $this->assertTrue(isset($field_storage_definitions[$this->instance->getName()]));
    $this->assertTrue($field_storage_definitions[$this->instance->getName()] instanceof FieldStorageDefinitionInterface);
    // Make sure no storage field for the instance on another entity type is
    // exposed.
    $this->assertFalse(isset($field_storage_definitions[$this->instance_rev->getName()]));
  }

}
