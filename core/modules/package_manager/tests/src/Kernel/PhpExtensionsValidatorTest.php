<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\PhpExtensionsValidator
 * @group package_manager
 * @internal
 */
class PhpExtensionsValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Data provider for ::testPhpExtensionsValidation().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerPhpExtensionsValidation(): array {
    $openssl_error = ValidationResult::createError([
      t('The OpenSSL extension is not enabled, which is a security risk. See <a href="https://www.php.net/manual/en/openssl.installation.php">the PHP documentation</a> for information on how to enable this extension.'),
    ]);
    $xdebug_warning = ValidationResult::createWarning([
      t('Xdebug is enabled, which may have a negative performance impact on Package Manager and any modules that use it.'),
    ]);
    return [
      'xdebug enabled, openssl installed' => [
        ['xdebug', 'openssl'],
        [$xdebug_warning],
        [],
      ],
      'xdebug enabled, openssl not installed' => [
        ['xdebug'],
        [$xdebug_warning, $openssl_error],
        [$openssl_error],
      ],
      'xdebug disabled, openssl installed' => [
        ['openssl'],
        [],
        [],
      ],
      'xdebug disabled, openssl not installed' => [
        [],
        [$openssl_error],
        [$openssl_error],
      ],
    ];
  }

  /**
   * Tests that PHP extensions' status are checked by Package Manager.
   *
   * @param string[] $loaded_extensions
   *   The names of the PHP extensions that the validator should think are
   *   loaded.
   * @param \Drupal\package_manager\ValidationResult[] $expected_status_check_results
   *   The expected validation results during the status check event.
   * @param \Drupal\package_manager\ValidationResult[] $expected_life_cycle_results
   *   The expected validation results during pre-create and pre-apply event.
   *
   * @dataProvider providerPhpExtensionsValidation
   */
  public function testPhpExtensionsValidation(array $loaded_extensions, array $expected_status_check_results, array $expected_life_cycle_results): void {
    $state = $this->container->get('state');
    // @see \Drupal\package_manager\Validator\PhpExtensionsValidator::isExtensionLoaded()
    $state->set('package_manager_loaded_php_extensions', $loaded_extensions);

    $this->assertStatusCheckResults($expected_status_check_results);
    $this->assertResults($expected_life_cycle_results, PreCreateEvent::class);
    // To test pre-apply delete the loaded extensions in state which will allow
    // the pre-create event to run without a validation error.
    $state->delete('package_manager_loaded_php_extensions');
    // On post-create set the loaded extensions in state so that the pre-apply
    // event will have the expected validation error.
    $this->addEventTestListener(function () use ($state, $loaded_extensions) {
      $state->set('package_manager_loaded_php_extensions', $loaded_extensions);
    }, PostCreateEvent::class);
    $this->assertResults($expected_life_cycle_results, PreApplyEvent::class);
  }

}
