<?php

namespace Drupal\Tests\field\Kernel\Entity;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

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

  /**
   * Tests validation of a field_config's default value.
   */
  public function testMultilineTextFieldDefaultValue(): void {
    // First, create a field storage for which a complex default value exists.
    $this->enableModules(['text']);
    $text_field_storage_config = FieldStorageConfig::create([
      'type' => 'text_with_summary',
      'field_name' => 'novel',
      'entity_type' => 'user',
    ]);
    $text_field_storage_config->save();

    $this->entity = FieldConfig::create([
      'field_storage' => $text_field_storage_config,
      'bundle' => 'user',
      'default_value' => [
        0 => [
          'value' => "Multi\nLine",
          'summary' => '',
          'format' => 'basic_html',
        ],
      ],
      'dependencies' => [
        'config' => [
          $text_field_storage_config->getConfigDependencyName(),
        ],
      ],
    ]);
    $this->assertValidationErrors([]);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    // If we don't clear the previous settings here, we will get unrelated
    // validation errors (in addition to the one we're expecting), because the
    // settings from the *old* field_type won't match the config schema for the
    // settings of the *new* field_type.
    $this->entity->set('settings', []);
    parent::testImmutableProperties($valid_values);
  }

}
