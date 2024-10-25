<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\SettingsValidator
 * @group package_manager
 * @internal
 */
class SettingsValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Data provider for testSettingsValidation().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerSettingsValidation(): array {
    $result = ValidationResult::createError([t('The <code>update_fetch_with_http_fallback</code> setting must be disabled.')]);

    return [
      'HTTP fallback enabled' => [TRUE, [$result]],
      'HTTP fallback disabled' => [FALSE, []],
    ];
  }

  /**
   * Tests settings validation before starting an update.
   *
   * @param bool $setting
   *   The value of the update_fetch_with_http_fallback setting.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerSettingsValidation
   */
  public function testSettingsValidation(bool $setting, array $expected_results): void {
    $this->setSetting('update_fetch_with_http_fallback', $setting);
    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Tests settings validation during pre-apply.
   *
   * @param bool $setting
   *   The value of the update_fetch_with_http_fallback setting.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerSettingsValidation
   */
  public function testSettingsValidationDuringPreApply(bool $setting, array $expected_results): void {
    $this->addEventTestListener(function () use ($setting): void {
      $this->setSetting('update_fetch_with_http_fallback', $setting);
    });
    $this->assertResults($expected_results, PreApplyEvent::class);
  }

}
