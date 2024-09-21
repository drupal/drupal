<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of field_config entities.
 *
 * @group field
 */
class FieldConfigValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'entity_test', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('node');
    $this->createContentType(['type' => 'one']);
    $this->createContentType(['type' => 'another']);

    EntityTestBundle::create(['id' => 'one'])->save();
    EntityTestBundle::create(['id' => 'another'])->save();

    $this->entity = FieldConfig::loadByName('node', 'one', 'body');
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
   * Tests that the target bundle of the field is checked.
   */
  public function testTargetBundleMustExist(): void {
    $this->entity->set('bundle', 'nope');
    $this->assertValidationErrors([
      '' => "The 'bundle' property cannot be changed.",
      'bundle' => "The 'nope' bundle does not exist on the 'node' entity type.",
    ]);
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
    parent::testImmutableProperties([
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'another',
      'field_type' => 'string',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testRequiredPropertyKeysMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    parent::testRequiredPropertyKeysMissing([
      'dependencies' => [
        // @see ::testInvalidDependencies()
        // @see \Drupal\Core\Config\Plugin\Validation\Constraint\RequiredConfigDependenciesConstraintValidator
        '' => 'This field requires a field storage.',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testRequiredPropertyValuesMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    parent::testRequiredPropertyValuesMissing([
      'dependencies' => [
        // @see ::testInvalidDependencies()
        // @see \Drupal\Core\Config\Plugin\Validation\Constraint\RequiredConfigDependenciesConstraintValidator
        '' => 'This field requires a field storage.',
      ],
    ]);
  }

  /**
   * Tests that the field type plugin's existence is validated.
   */
  public function testFieldTypePluginIsValidated(): void {
    // The `field_type` property is immutable, so we need to clone the entity in
    // order to cleanly change its immutable properties.
    $this->entity = $this->entity->createDuplicate()
      // We need to clear the current settings, or we will get validation errors
      // because the old settings are not supported by the new field type.
      ->set('settings', [])
      ->set('field_type', 'invalid');

    $this->assertValidationErrors([
      'field_type' => "The 'invalid' plugin does not exist.",
    ]);
  }

  /**
   * Tests that entity reference selection handler plugin IDs are validated.
   */
  public function testEntityReferenceSelectionHandlerIsValidated(): void {
    $this->container->get('state')
      ->set('field_test_disable_broken_entity_reference_handler', TRUE);
    $this->enableModules(['field_test']);

    // The `field_type` property is immutable, so we need to clone the entity in
    // order to cleanly change its immutable properties.
    $this->entity = $this->entity->createDuplicate()
      ->set('field_type', 'entity_reference')
      ->set('settings', ['handler' => 'non_existent']);

    $this->assertValidationErrors([
      'settings.handler' => "The 'non_existent' plugin does not exist.",
    ]);
  }

}
