<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Validation;

use Drupal\file_test\FileTestHelper;

/**
 * Tests the file validator.
 *
 * @group file
 */
class FileValidatorTest extends FileValidatorTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'file_test',
    'file_validator_test',
    'user',
    'system',
  ];

  /**
   * Tests the validator.
   */
  public function testValidate(): void {
    // Use plugin IDs to test they work.
    // Each Constraint has its own tests under
    // core/modules/file/tests/src/Kernel/Plugin/Validation/Constraint.
    $validators = [
      'FileNameLength' => [],
    ];
    FileTestHelper::reset();

    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(0, $violations);
    $this->assertCount(1, FileTestHelper::getCalls('validate'));

    FileTestHelper::reset();
    $this->file->set('filename', '');
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(1, $violations);
    $this->assertEquals($violations[0]->getMessage(), $violations[0]->getMessage(), 'Message names are equal');
    $this->assertCount(1, FileTestHelper::getCalls('validate'));

    FileTestHelper::reset();
    $this->file->set('filename', $this->randomMachineName(241));
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(1, $violations);
    $this->assertEquals("The file's name exceeds the 240 characters limit. Rename the file and try again.", $violations[0]->getMessage());
    $this->assertCount(1, FileTestHelper::getCalls('validate'));
  }

}
