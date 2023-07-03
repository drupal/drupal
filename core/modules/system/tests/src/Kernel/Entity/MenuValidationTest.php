<?php

namespace Drupal\Tests\system\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\system\Entity\Menu;

/**
 * Tests validation of menu entities.
 *
 * @group system
 */
class MenuValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Menu::create([
      'id' => 'test-menu',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

  /**
   * Menu IDs are atypical: they allow dashes and disallow underscores.
   */
  public function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();

    // Remove the existing test case that verifies a machine name containing
    // dashes is invalid.
    $this->assertSame(['dash-separated', FALSE], $cases['INVALID: dash separated']);
    unset($cases['INVALID: dash separated']);
    // And instead add a test case that verifies it is allowed for menus.
    $cases['VALID: dash separated'] = ['dash-separated', TRUE];

    // Remove the existing test case that verifies a machine name containing
    // underscores is valid.
    $this->assertSame(['underscore_separated', TRUE], $cases['VALID: underscore separated']);
    unset($cases['VALID: underscore separated']);
    // And instead add a test case that verifies it is disallowed for menus.
    $cases['INVALID: underscore separated'] = ['underscore_separated', FALSE];

    return $cases;
  }

}
