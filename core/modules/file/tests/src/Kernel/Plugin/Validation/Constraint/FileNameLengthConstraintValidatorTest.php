<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Plugin\Validation\Constraint;

use Drupal\file\Entity\File;
use Drupal\Tests\file\Kernel\Validation\FileValidatorTestBase;

/**
 * Tests the FileNameLengthConstraintValidator.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Plugin\Validation\Constraint\FileNameLengthConstraintValidator
 */
class FileNameLengthConstraintValidatorTest extends FileValidatorTestBase {

  /**
   * This will ensure the filename length is valid.
   *
   * @covers ::validate
   */
  public function testFileValidateNameLength(): void {
    // Create a new file entity.
    $file = File::create();

    // Add a filename with an allowed length and test it.
    $file->setFilename(str_repeat('x', 240));
    $this->assertEquals(240, strlen($file->getFilename()));
    $validators = ['FileNameLength' => []];
    $violations = $this->validator->validate($file, $validators);
    $this->assertCount(0, $violations, 'No errors reported for 240 length filename.');

    // Add a filename with a length too long and test it.
    $file->setFilename(str_repeat('x', 241));
    $violations = $this->validator->validate($file, $validators);
    $this->assertCount(1, $violations, 'An error reported for 241 length filename.');

    // Add a filename with an empty string and test it.
    $file->setFilename('');
    $violations = $this->validator->validate($file, $validators);
    $this->assertCount(1, $violations, 'An error reported for 0 length filename.');
  }

}
