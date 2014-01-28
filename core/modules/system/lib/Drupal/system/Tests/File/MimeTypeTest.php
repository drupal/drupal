<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\MimeTypeTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests for file_get_mimetype().
 */
class MimeTypeTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  public static function getInfo() {
    return array(
      'name' => 'File mimetypes',
      'description' => 'Test filename mimetype detection.',
      'group' => 'File API',
    );
  }

  /**
   * Test mapping of mimetypes from filenames.
   */
  public function testFileMimeTypeDetection() {
    $prefix = 'public://';

    $test_case = array(
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'image/jpeg',
      'test.JPEG' => 'image/jpeg',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.Z' => 'application/x-font',
      'pcf.z' => 'application/octet-stream',
      'jar' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
      'foo.file_test_1' => 'madeup/file_test_1',
      'foo.file_test_2' => 'madeup/file_test_2',
      'foo.doc' => 'madeup/doc',
      'test.ogg' => 'audio/ogg',
    );

    // Test using default mappings.
    foreach ($test_case as $input => $expected) {
      // Test stream [URI].
      $output = file_get_mimetype($prefix . $input);
      $this->assertIdentical($output, $expected, format_string('Mimetype for %input is %output (expected: %expected).', array('%input' => $input, '%output' => $output, '%expected' => $expected)));

      // Test normal path equivalent
      $output = file_get_mimetype($input);
      $this->assertIdentical($output, $expected, format_string('Mimetype (using default mappings) for %input is %output (expected: %expected).', array('%input' => $input, '%output' => $output, '%expected' => $expected)));
    }

    // Now test passing in the map.
    $mapping = array(
      'mimetypes' => array(
        0 => 'application/java-archive',
        1 => 'image/jpeg',
      ),
      'extensions' => array(
         'jar' => 0,
         'jpg' => 1,
      )
    );

    $test_case = array(
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'application/octet-stream',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.z' => 'application/octet-stream',
      'pcf.z' => 'application/octet-stream',
      'jar' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
      'foo.file_test_1' => 'application/octet-stream',
      'foo.file_test_2' => 'application/octet-stream',
      'foo.doc' => 'application/octet-stream',
      'test.ogg' => 'application/octet-stream',
    );

    foreach ($test_case as $input => $expected) {
      $output = file_get_mimetype($input, $mapping);
      $this->assertIdentical($output, $expected, format_string('Mimetype (using passed-in mappings) for %input is %output (expected: %expected).', array('%input' => $input, '%output' => $output, '%expected' => $expected)));
    }
  }
}
