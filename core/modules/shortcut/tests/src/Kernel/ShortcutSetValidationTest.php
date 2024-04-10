<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\shortcut\Entity\ShortcutSet;

/**
 * Tests validation of shortcut_set entities.
 *
 * @group shortcut
 */
class ShortcutSetValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['link', 'shortcut'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('shortcut');
    $this->installEntitySchema('shortcut');

    $this->entity = ShortcutSet::create([
      'id' => 'test-shortcut-set',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

  /**
   * Shortcut set IDs are atypical: they allow dashes and disallow underscores.
   */
  public static function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();

    // Remove the existing test case that verifies a machine name containing
    // dashes is invalid.
    self::assertSame(['dash-separated', FALSE], $cases['INVALID: dash separated']);
    unset($cases['INVALID: dash separated']);
    // And instead add a test case that verifies it is allowed for shortcut
    // sets.
    $cases['VALID: dash separated'] = ['dash-separated', TRUE];

    // Remove the existing test case that verifies a machine name containing
    // underscores is valid.
    self::assertSame(['underscore_separated', TRUE], $cases['VALID: underscore separated']);
    unset($cases['VALID: underscore separated']);
    // And instead add a test case that verifies it is disallowed for shortcut
    // sets.
    $cases['INVALID: underscore separated'] = ['underscore_separated', FALSE];

    return $cases;
  }

}
