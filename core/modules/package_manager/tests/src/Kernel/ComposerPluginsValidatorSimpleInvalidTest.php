<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

/**
 * @covers \Drupal\package_manager\Validator\ComposerPluginsValidator
 * @group package_manager
 * @group #slow
 * @internal
 */
class ComposerPluginsValidatorSimpleInvalidTest extends ComposerPluginsValidatorTestBase {

  /**
   * Tests composer plugins are validated during pre-create.
   *
   * @dataProvider providerSimpleInvalidCases
   */
  public function testValidationDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests composer plugins are validated during pre-apply.
   *
   * @dataProvider providerSimpleInvalidCases
   */
  public function testValidationDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests additional composer plugins can be trusted during pre-create.
   *
   * @dataProvider providerSimpleInvalidCases
   */
  public function testValidationAfterTrustingDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationAfterTrustingDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests additional composer plugins can be trusted during pre-apply.
   *
   * @dataProvider providerSimpleInvalidCases
   */
  public function testValidationAfterTrustingDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationAfterTrustingDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results);
  }

}
