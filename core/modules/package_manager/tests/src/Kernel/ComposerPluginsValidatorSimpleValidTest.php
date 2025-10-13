<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Validator\ComposerPluginsValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Composer Plugins Validator Simple Valid.
 *
 * @internal
 */
#[Group('#slow')]
#[Group('package_manager')]
#[CoversClass(ComposerPluginsValidator::class)]
#[RunTestsInSeparateProcesses]
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
