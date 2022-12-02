<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests validation of entity_form_display entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityFormDisplayValidationTest extends EntityFormModeValidationTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = EntityFormDisplay::create([
      'label' => 'Test',
      'targetEntityType' => 'user',
      'bundle' => 'user',
      // The mode was created by the parent class.
      'mode' => 'test',
    ]);
    $this->entity->save();
  }

}
