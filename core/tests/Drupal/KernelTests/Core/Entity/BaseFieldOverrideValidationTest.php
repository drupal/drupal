<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of base_field_override entities.
 *
 * @group Entity
 * @group Validation
 */
class BaseFieldOverrideValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'node', 'text', 'user'];

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

    $fields = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('node');

    $this->entity = BaseFieldOverride::createFromBaseFieldDefinition($fields['uuid'], 'one');
    $this->entity->save();
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
   * Tests that the field type plugin's existence is validated.
   */
  public function testFieldTypePluginIsValidated(): void {
    // The `field_type` property is immutable, so we need to clone the entity in
    // order to cleanly change its field_type property to some invalid value.
    $this->entity = $this->entity->createDuplicate()
      ->set('field_type', 'invalid');
    $this->assertValidationErrors([
      'field_type' => "The 'invalid' plugin does not exist.",
    ]);
  }

}
