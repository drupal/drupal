<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Composer Plugins Validator Simple Valid.
 *
 * @internal
 * @legacy-covers \Drupal\package_manager\Validator\ComposerPluginsValidator
 */
#[Group('#slow')]
#[Group('package_manager')]
class ComposerPluginsValidatorSimpleValidTest extends ComposerPluginsValidatorTestBase {

  /**
   * Tests composer plugins are validated during pre-create.
   */
  #[DataProvider('providerSimpleValidCases')]
  public function testValidationDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results);
  }

  /**
   * Tests composer plugins are validated during pre-apply.
   */
  #[DataProvider('providerSimpleValidCases')]
  public function testValidationDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $this->doTestValidationDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results);
  }

}
