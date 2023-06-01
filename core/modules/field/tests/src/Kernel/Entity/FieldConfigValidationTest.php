<?php

namespace Drupal\Tests\field\Kernel\Entity;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests validation of field_config entities.
 *
 * @group field
 */
class FieldConfigValidationTest extends FieldStorageConfigValidationTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The field storage was created in the parent method.
    $field_storage = $this->entity;

    $this->entity = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'user',
    ]);
    $this->entity->save();
  }

  /**
   * Tests that validation fails if config dependencies are invalid.
   */
  public function testInvalidDependencies(): void {
    // Remove the config dependencies from the field entity.
    $dependencies = $this->entity->getDependencies();
    $dependencies['config'] = [];
    $this->entity->set('dependencies', $dependencies);

    $this->assertValidationErrors(['' => 'This field requires a field storage.']);

    // Things look sort-of like `field.storage.*.*` should fail validation
    // because they don't exist.
    $dependencies['config'] = [
      'field.storage.fake',
      'field.storage.',
      'field.storage.user.',
    ];
    $this->entity->set('dependencies', $dependencies);
    $this->assertValidationErrors([
      'dependencies.config.0' => "The 'field.storage.fake' config does not exist.",
      'dependencies.config.1' => "The 'field.storage.' config does not exist.",
      'dependencies.config.2' => "The 'field.storage.user.' config does not exist.",
    ]);
  }

}
