<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Composer Plugins Validator Complex Invalid.
 *
 * @internal
 * @legacy-covers \Drupal\package_manager\Validator\ComposerPluginsValidator
 */
#[Group('package_manager')]
#[Group('#slow')]
class ComposerPluginsValidatorComplexInvalidTest extends ComposerPluginsValidatorTestBase {

  /**
   * Tests composer plugins are validated during pre-create.
   */
  #[DataProvider('providerComplexInvalidCases')]
  public function testValidationDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests composer plugins are validated during pre-apply.
   */
  #[DataProvider('providerComplexInvalidCases')]
  public function testValidationDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests additional composer plugins can be trusted during pre-create.
   */
  #[DataProvider('providerComplexInvalidCases')]
  public function testValidationAfterTrustingDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationAfterTrustingDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests additional composer plugins can be trusted during pre-apply.
   */
  #[DataProvider('providerComplexInvalidCases')]
  public function testValidationAfterTrustingDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationAfterTrustingDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results);
  }

}
