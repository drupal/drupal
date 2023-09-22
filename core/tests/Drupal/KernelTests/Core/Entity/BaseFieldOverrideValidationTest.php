<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of base_field_override entities.
 *
 * @group Entity
 * @group Validation
 */
class BaseFieldOverrideValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $fields = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('user');

    $this->entity = BaseFieldOverride::createFromBaseFieldDefinition(reset($fields), 'user');
    $this->entity->save();
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
