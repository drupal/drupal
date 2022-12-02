<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests validation of entity_view_display entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityViewDisplayValidationTest extends EntityViewModeValidationTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = EntityViewDisplay::create([
      'label' => 'Test',
      'targetEntityType' => 'user',
      'bundle' => 'user',
      // The mode was created by the parent class.
      'mode' => 'test',
    ]);
    $this->entity->save();
  }

}
