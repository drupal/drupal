<?php

/**
 * @file
 * Definition of Drupal\file\Tests\ValidateTest.
 */

namespace Drupal\file\Tests;

/**
 * Tests the file_validate() function.
 *
 * @group file
 */
class ValidateTest extends FileManagedUnitTestBase {
  /**
   * Test that the validators passed into are checked.
   */
  function testCallerValidation() {
    $file = $this->createFile();

    // Empty validators.
    $this->assertEqual(file_validate($file, array()), array(), 'Validating an empty array works successfully.');
    $this->assertFileHooksCalled(array('validate'));

    // Use the file_test.module's test validator to ensure that passing tests
    // return correctly.
    file_test_reset();
    file_test_set_return('validate', array());
    $passing = array('file_test_validator' => array(array()));
    $this->assertEqual(file_validate($file, $passing), array(), 'Validating passes.');
    $this->assertFileHooksCalled(array('validate'));

    // Now test for failures in validators passed in and by hook_validate.
    file_test_reset();
    file_test_set_return('validate', array('Epic fail'));
    $failing = array('file_test_validator' => array(array('Failed', 'Badly')));
    $this->assertEqual(file_validate($file, $failing), array('Failed', 'Badly', 'Epic fail'), 'Validating returns errors.');
    $this->assertFileHooksCalled(array('validate'));
  }
}
