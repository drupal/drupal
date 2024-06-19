<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

/**
 * Tests the file_validate() function.
 *
 * @group file
 * @group legacy
 */
class LegacyValidateTest extends FileManagedUnitTestBase {

  /**
   * Tests that the validators passed into are checked.
   */
  public function testCallerValidation(): void {
    $file = $this->createFile();

    // Empty validators.
    $this->expectDeprecation('file_validate() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'file.validator\' service instead. See https://www.drupal.org/node/3363700');
    $this->assertEquals([], file_validate($file, []), 'Validating an empty array works successfully.');
    $this->assertFileHooksCalled([]);

    // Use the file_test.module's test validator to ensure that passing tests
    // return correctly.
    file_test_reset();
    file_test_set_return('validate', []);
    $passing = ['file_test_validator' => [[]]];
    $this->assertEquals([], file_validate($file, $passing), 'Validating passes.');
    $this->assertFileHooksCalled([]);

    // Now test for failures in validators passed in and by hook_validate.
    file_test_reset();
    $failing = ['file_test_validator' => [['Failed', 'Badly']]];
    $this->assertEquals(['Failed', 'Badly'], file_validate($file, $failing), 'Validating returns errors.');
    $this->assertFileHooksCalled([]);
  }

  /**
   * Tests hard-coded security check in file_validate().
   */
  public function testInsecureExtensions(): void {
    $file = $this->createFile('test.php', 'Invalid PHP');

    // Test that file_validate() will check for insecure extensions by default.
    $this->expectDeprecation('file_validate() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'file.validator\' service instead. See https://www.drupal.org/node/3363700');
    $errors = file_validate($file, []);
    $this->assertEquals('For security reasons, your upload has been rejected.', $errors[0]);
    $this->assertFileHooksCalled([]);
    file_test_reset();

    // Test that the 'allow_insecure_uploads' is respected.
    $this->config('system.file')->set('allow_insecure_uploads', TRUE)->save();
    $errors = file_validate($file, []);
    $this->assertEmpty($errors);
    $this->assertFileHooksCalled([]);
  }

}
