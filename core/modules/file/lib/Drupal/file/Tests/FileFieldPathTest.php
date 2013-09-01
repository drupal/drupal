<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileFieldPathTest.
 */

namespace Drupal\file\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests that files are uploaded to proper locations.
 */
class FileFieldPathTest extends FileFieldTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File field file path tests',
      'description' => 'Test that files are uploaded to the proper location with token support.',
      'group' => 'File',
    );
  }

  /**
   * Tests the normal formatter display on node display.
   */
  function testUploadPath() {
    $field_name = strtolower($this->randomName());
    $type_name = 'article';
    $this->createFileField($field_name, 'node', $type_name);
    $test_file = $this->getTestFile('text');

    // Create a new node.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the file was uploaded to the file root.
    $node = node_load($nid, TRUE);
    $node_file = file_load($node->{$field_name}->target_id);
    $this->assertPathMatch('public://' . $test_file->getFilename(), $node_file->getFileUri(), format_string('The file %file was uploaded to the correct path.', array('%file' => $node_file->getFileUri())));

    // Change the path to contain multiple subdirectories.
    $this->updateFileField($field_name, $type_name, array('file_directory' => 'foo/bar/baz'));

    // Upload a new file into the subdirectories.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the file was uploaded into the subdirectory.
    $node = node_load($nid, TRUE);
    $node_file = file_load($node->{$field_name}->target_id, TRUE);
    $this->assertPathMatch('public://foo/bar/baz/' . $test_file->getFilename(), $node_file->getFileUri(), format_string('The file %file was uploaded to the correct path.', array('%file' => $node_file->getFileUri())));

    // Check the path when used with tokens.
    // Change the path to contain multiple token directories.
    $this->updateFileField($field_name, $type_name, array('file_directory' => '[current-user:uid]/[current-user:name]'));

    // Upload a new file into the token subdirectories.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Check that the file was uploaded into the subdirectory.
    $node = node_load($nid, TRUE);
    $node_file = file_load($node->{$field_name}->target_id);
    // Do token replacement using the same user which uploaded the file, not
    // the user running the test case.
    $data = array('user' => $this->admin_user);
    $subdirectory = \Drupal::token()->replace('[user:uid]/[user:name]', $data);
    $this->assertPathMatch('public://' . $subdirectory . '/' . $test_file->getFilename(), $node_file->getFileUri(), format_string('The file %file was uploaded to the correct path with token replacements.', array('%file' => $node_file->getFileUri())));
  }

  /**
   * Asserts that a file is uploaded to the right location.
   *
   * @param $expected_path
   *   The location where the file is expected to be uploaded. Duplicate file
   *   names to not need to be taken into account.
   * @param $actual_path
   *   Where the file was actually uploaded.
   * @param $message
   *   The message to display with this assertion.
   */
  function assertPathMatch($expected_path, $actual_path, $message) {
    // Strip off the extension of the expected path to allow for _0, _1, etc.
    // suffixes when the file hits a duplicate name.
    $pos = strrpos($expected_path, '.');
    $base_path = substr($expected_path, 0, $pos);
    $extension = substr($expected_path, $pos + 1);

    $result = preg_match('/' . preg_quote($base_path, '/') . '(_[0-9]+)?\.' . preg_quote($extension, '/') . '/', $actual_path);
    $this->assertTrue($result, $message);
  }
}
