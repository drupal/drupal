<?php

namespace Drupal\KernelTests\Core\Field;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the exception when missing a field type.
 *
 * @group Field
 */
class FieldMissingTypeTest extends EntityKernelTestBase {

  /**
   * Set to FALSE because we are hacking a field storage to use a fake type.
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity_type_id = 'entity_test_mulrev';
    $this->installEntitySchema($entity_type_id);
    $this->fieldName = mb_strtolower($this->randomMachineName());

    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'text',
      'entity_type' => $entity_type_id,
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => $this->fieldName,
      'bundle' => $entity_type_id,
      'label' => 'Test field',
    ])->save();
  }

  /**
   * Tests the exception thrown when missing a field type in field storages.
   *
   * @see \Drupal\field\FieldStorageConfigStorage::mapFromStorageRecords()
   */
  public function testFieldStorageMissingType() {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Unable to determine class for field type 'foo_field_storage' found in the 'field.storage.entity_test_mulrev.{$this->fieldName}' configuration");
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'field_test_item' => $this->randomString(),
      $this->fieldName => $this->randomString(),
    ]);
    $entity->save();
    // Hack the field storage to use a non-existent field type.
    $this->config('field.storage.entity_test_mulrev.' . $this->fieldName)->set('type', 'foo_field_storage')->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    EntityTestMulRev::load($entity->id());
  }

  /**
   * Tests the exception thrown when missing a field type in fields.
   *
   * @see \Drupal\field\FieldConfigStorageBase::mapFromStorageRecords()
   */
  public function testFieldMissingType() {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Unable to determine class for field type 'foo_field' found in the 'field.field.entity_test_mulrev.entity_test_mulrev.{$this->fieldName}' configuration");
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'field_test_item' => $this->randomString(),
      $this->fieldName => $this->randomString(),
    ]);
    $entity->save();
    // Hack the field to use a non-existent field type.
    $this->config('field.field.entity_test_mulrev.entity_test_mulrev.' . $this->fieldName)->set('field_type', 'foo_field')->save();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    EntityTestMulRev::load($entity->id());
  }

}
