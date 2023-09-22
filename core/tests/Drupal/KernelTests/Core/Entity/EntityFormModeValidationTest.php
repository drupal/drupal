<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of entity_form_mode entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityFormModeValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');

    $this->entity = EntityFormMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ]);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    $valid_values['id'] = 'user.test_changed';
    parent::testImmutableProperties($valid_values);
  }

}
