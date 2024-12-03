<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

/**
 * @covers \Drupal\package_manager\Validator\ComposerPluginsValidator
 * @group #slow
 * @group package_manager
 * @internal
 */
class ComposerPluginsValidatorSimpleValidTest extends ComposerPluginsValidatorTestBase {

  /**
   * Tests composer plugins are validated during pre-create.
   *
   * @dataProvider providerSimpleValidCases
   */
  public function testValidationDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests composer plugins are validated during pre-apply.
   *
   * @dataProvider providerSimpleValidCases
   */
  public function testValidationDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results);
  }

}
