<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Entity;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of field_storage_config entities.
 *
 * @group field
 * @group #slow
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

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    $valid_values['type'] = 'string';
    parent::testImmutableProperties($valid_values);
  }

  /**
   * Tests that the field type plugin's existence is validated.
   */
  public function testFieldTypePluginIsValidated(): void {
    // The `type` property is immutable, so we need to clone the entity in
    // order to cleanly change its immutable properties.
    $this->entity = $this->entity->createDuplicate()
      ->set('type', 'invalid');

    $this->assertValidationErrors([
      'type' => "The 'invalid' plugin does not exist.",
    ]);
  }

}
