<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Validation;

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
   *
   * @group legacy
   */
  public function testValidate(): void {
    // Use a mix of legacy functions and plugin IDs to test both work.
    // Each Constraint has its own tests under
    // core/modules/file/tests/src/Kernel/Plugin/Validation/Constraint.
    // Also check that arbitrary strings can be used.
    $validators = [
      'file_validate_name_length' => [],
      'FileNameLength' => [],
      'foo' => [],
    ];
    file_test_reset();

    $this->expectDeprecation('Support for file validation function file_validate_name_length() is deprecated in drupal:10.2.0 and will be removed in drupal:11.0.0. Use Symfony Constraints instead. See https://www.drupal.org/node/3363700');
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(0, $violations);
    $this->assertCount(1, file_test_get_calls('validate'));

    $this->expectDeprecation('Passing invalid constraint plugin ID "foo" in the list of $validators to Drupal\file\Validation\FileValidator::validate() is deprecated in drupal:10.2.0 and will throw an exception in drupal:11.0.0. See https://www.drupal.org/node/3363700');
    file_test_reset();
    $this->file->set('filename', '');
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(2, $violations);
    $this->assertEquals($violations[0]->getMessage(), $violations[1]->getMessage(), 'Message names are equal');
    $this->assertCount(1, file_test_get_calls('validate'));

    file_test_reset();
    $this->file->set('filename', $this->randomMachineName(241));
    $violations = $this->validator->validate($this->file, $validators);
    $this->assertCount(2, $violations);
    $this->assertEquals("The file's name exceeds the 240 characters limit. Rename the file and try again.", $violations[0]->getMessage());
    $this->assertEquals("The file's name exceeds the 240 characters limit. Rename the file and try again.", $violations[1]->getMessage());
    $this->assertCount(1, file_test_get_calls('validate'));
  }

}
