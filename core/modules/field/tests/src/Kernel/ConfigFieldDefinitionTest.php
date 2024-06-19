<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Tests exposing field definitions for configurable fields.
 *
 * @group field
 */
class ConfigFieldDefinitionTest extends FieldKernelTestBase {

  /**
   * @var string
   */
  private $entityType;

  /**
   * @var string
   */
  private $bundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a field and a storage of type 'test_field', on the 'entity_test'
    // entity type.
    $this->entityType = 'entity_test';
    $this->bundle = 'entity_test';
    $this->createFieldWithStorage('', $this->entityType, $this->bundle);

    // Create a second field on 'entity_test_rev'.
    $this->installEntitySchema('entity_test_rev');
    $this->createFieldWithStorage('_rev', 'entity_test_rev', 'entity_test_rev');
  }

  /**
   * Makes sure a field definition is exposed for a configurable field.
   */
  public function testBundleFieldDefinition(): void {
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->entityType, $this->bundle);
    $this->assertTrue(isset($definitions[$this->fieldTestData->field->getName()]));
    $this->assertInstanceOf(FieldDefinitionInterface::class, $definitions[$this->fieldTestData->field->getName()]);
    // Make sure fields on other entity types are not exposed.
    $this->assertFalse(isset($definitions[$this->fieldTestData->field_rev->getName()]));
  }

  /**
   * Makes sure a field storage definition is exposed for a configurable field.
   */
  public function testFieldStorageDefinition(): void {
    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($this->entityType);
    $this->assertTrue(isset($field_storage_definitions[$this->fieldTestData->field->getName()]));
    $this->assertInstanceOf(FieldStorageDefinitionInterface::class, $field_storage_definitions[$this->fieldTestData->field->getName()]);
    // Make sure storages on other entity types are not exposed.
    $this->assertFalse(isset($field_storage_definitions[$this->fieldTestData->field_rev->getName()]));
  }

}
