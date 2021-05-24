<?php

namespace Drupal\Tests\file\Kernel;

/**
 * Tests the file_validate() function.
 *
 * @group file
 */
class ValidateTest extends FileManagedUnitTestBase {

  /**
   * Test that the validators passed into are checked.
   */
  public function testCallerValidation() {
    $file = $this->createFile();

    // Empty validators.
    $this->assertEquals([], file_validate($file, []), 'Validating an empty array works successfully.');
    $this->assertFileHooksCalled(['validate']);

    // Use the file_test.module's test validator to ensure that passing tests
    // return correctly.
    file_test_reset();
    file_test_set_return('validate', []);
    $passing = ['file_test_validator' => [[]]];
    $this->assertEquals([], file_validate($file, $passing), 'Validating passes.');
    $this->assertFileHooksCalled(['validate']);

    // Now test for failures in validators passed in and by hook_validate.
    file_test_reset();
    file_test_set_return('validate', ['Epic fail']);
    $failing = ['file_test_validator' => [['Failed', 'Badly']]];
    $this->assertEquals(['Failed', 'Badly', 'Epic fail'], file_validate($file, $failing), 'Validating returns errors.');
    $this->assertFileHooksCalled(['validate']);
  }

  /**
   * Tests hard-coded security check in file_validate().
   */
  public function testInsecureExtensions() {
    $file = $this->createFile('test.php', 'Invalid PHP');

    // Test that file_validate() will check for insecure extensions by default.
    $errors = file_validate($file, []);
    $this->assertEquals('For security reasons, your upload has been rejected.', $errors[0]);
    $this->assertFileHooksCalled(['validate']);
    file_test_reset();

    // Test that the 'allow_insecure_uploads' is respected.
    $this->config('system.file')->set('allow_insecure_uploads', TRUE)->save();
    $errors = file_validate($file, []);
    $this->assertEmpty($errors);
    $this->assertFileHooksCalled(['validate']);
  }

}
