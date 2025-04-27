<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\ComposerMinimumStabilityValidator
 * @group package_manager
 * @internal
 */
class ComposerMinimumStabilityValidatorTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  /**
   * Tests error if requested version is less stable than the minimum: stable.
   */
  public function testPreRequireEvent(): void {
    $stage = $this->createStage();
    $stage->create();
    $result = ValidationResult::createError([
      $this->t("<code>drupal/core</code>'s requested version 9.8.1-beta1 is less stable (beta) than the minimum stability (stable) required in <PROJECT_ROOT>/composer.json."),
    ]);
    try {
      $stage->require(['drupal/core:9.8.1-beta1']);
      $this->fail('Able to require a package even though it did not meet minimum stability.');
    }
    catch (SandboxEventException $exception) {
      $this->assertValidationResultsEqual([$result], $exception->event->getResults());
    }
    $stage->destroy();

    // Specifying a stability flag bypasses this check.
    $stage->create();
    $stage->require(['drupal/core:9.8.1-beta1@dev']);
    $stage->destroy();

    // Dev packages are also checked.
    $stage->create();
    $result = ValidationResult::createError([
      $this->t("<code>drupal/core-dev</code>'s requested version 9.8.x-dev is less stable (dev) than the minimum stability (stable) required in <PROJECT_ROOT>/composer.json."),
    ]);
    try {
      $stage->require([], ['drupal/core-dev:9.8.x-dev']);
      $this->fail('Able to require a package even though it did not meet minimum stability.');
    }
    catch (SandboxEventException $exception) {
      $this->assertValidationResultsEqual([$result], $exception->event->getResults());
    }
  }

}
