<?php

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests validation of action entities.
 *
 * @group system
 */
class ActionValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Action::create([
      'id' => 'test',
      'label' => 'Test',
      'type' => 'test',
      'plugin' => 'action_goto_action',
    ]);
    $this->entity->save();
  }

}
