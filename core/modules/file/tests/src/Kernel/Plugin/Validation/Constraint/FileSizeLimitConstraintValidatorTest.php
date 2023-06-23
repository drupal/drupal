<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;

/**
 * Tests the FileSizeLimitConstraintValidator.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Plugin\Validation\Constraint\FileSizeLimitConstraintValidator
 */
class FileSizeLimitConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * @covers ::validate
   */
  public function testFileValidateSize() {
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
