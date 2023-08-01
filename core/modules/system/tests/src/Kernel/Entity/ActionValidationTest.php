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

  /**
   * Action IDs are atypical in that they allow periods in the machine name.
   */
  public function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();
    // Remove the existing test case that verifies a machine name containing
    // periods is invalid.
    $this->assertSame(['period.separated', FALSE], $cases['INVALID: period separated']);
    unset($cases['INVALID: period separated']);
    // And instead add a test case that verifies it is allowed for blocks.
    $cases['VALID: period separated'] = ['period.separated', TRUE];
    return $cases;
  }

}
