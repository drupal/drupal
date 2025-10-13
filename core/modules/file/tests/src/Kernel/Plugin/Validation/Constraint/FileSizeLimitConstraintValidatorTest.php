<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Plugin\Validation\Constraint\FileSizeLimitConstraintValidator;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the FileSizeLimitConstraintValidator.
 */
#[CoversClass(FileSizeLimitConstraintValidator::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class FileSizeLimitConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * Tests file validate size.
   *
   * @legacy-covers ::validate
   */
  public function testFileValidateSize(): void {
    $validators = ['FileSizeLimit' => []];
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(0, $violations, 'No limits means no errors.');
    $validators = ['FileSizeLimit' => ['fileLimit' => 1]];
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(1, $violations, 'Error for the file being over the limit.');
    $validators = ['FileSizeLimit' => ['userLimit' => 1]];
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(1, $violations, 'Error for the user being over their limit.');
    $validators = ['FileSizeLimit' => ['fileLimit' => 1, 'userLimit' => 1]];
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(2, $violations, 'Errors for both the file and their limit.');
  }

}
