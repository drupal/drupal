<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Plugin\Validation\Constraint\FileExtensionSecureConstraintValidator;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the FileExtensionSecureConstraintValidator.
 */
#[CoversClass(FileExtensionSecureConstraintValidator::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class FileExtensionSecureConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * Tests validate.
   *
   * @legacy-covers ::validate
   */
  public function testValidate(): void {
    // Test success with .txt extension.
    $validators = [
      'FileExtensionSecure' => [],
    ];
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(0, $violations);

    // Test failure with .php extension.
    $this->file->setFilename('foo.php');
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(1, $violations);
    $this->assertEquals('For security reasons, your upload has been rejected.', $violations->get(0)->getMessage());

    // Test success with .php extension and allow_insecure_uploads.
    $this->config('system.file')->set('allow_insecure_uploads', TRUE)->save();
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(0, $violations);
  }

}
