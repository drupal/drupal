<?php

namespace Drupal\Tests\field\Kernel\Entity;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of field_storage_config entities.
 *
 * @group field
 */
class FieldStorageConfigValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    $this->entity = FieldStorageConfig::create([
      'type' => 'boolean',
      'field_name' => 'test',
      'entity_type' => 'user',
    ]);
    $this->entity->save();
  }

}
