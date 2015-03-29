<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileTokenReplaceTest.
 */

namespace Drupal\file\Tests;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Generates text using placeholders for dummy content to check file token
 * replacement.
 *
 * @group file
 */
class FileTokenReplaceTest extends FileFieldTestBase {
  /**
   * Creates a file, then tests the tokens generated from it.
   */
  function testFileTokenReplacement() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Create file field.
    $type_name = 'article';
    $field_name = 'field_' . strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('text');
    // Coping a file to test uploads with non-latin filenames.
    $filename = drupal_dirname($test_file->getFileUri()) . '/текстовый файл.txt';
    $test_file = file_copy($test_file, $filename);

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Load the node and the file.
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $file = file_load($node->{$field_name}->target_id);

    // Generate and test sanitized tokens.
    $tests = array();
    $tests['[file:fid]'] = $file->id();
    $tests['[file:name]'] = SafeMarkup::checkPlain($file->getFilename());
    $tests['[file:path]'] = SafeMarkup::checkPlain($file->getFileUri());
    $tests['[file:mime]'] = SafeMarkup::checkPlain($file->getMimeType());
    $tests['[file:size]'] = format_size($file->getSize());
    $tests['[file:url]'] = SafeMarkup::checkPlain(file_create_url($file->getFileUri()));
    $tests['[file:created]'] = format_date($file->getCreatedTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[file:created:short]'] = format_date($file->getCreatedTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[file:changed]'] = format_date($file->getChangedTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[file:changed:short]'] = format_date($file->getChangedTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[file:owner]'] = SafeMarkup::checkPlain(user_format_name($this->adminUser));
    $tests['[file:owner:uid]'] = $file->getOwnerId();

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('file' => $file), array('langcode' => $language_interface->getId()));
      $this->assertEqual($output, $expected, format_string('Sanitized file token %token replaced.', array('%token' => $input)));
    }

    // Generate and test unsanitized tokens.
    $tests['[file:name]'] = $file->getFilename();
    $tests['[file:path]'] = $file->getFileUri();
    $tests['[file:mime]'] = $file->getMimeType();
    $tests['[file:size]'] = format_size($file->getSize());

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, array('file' => $file), array('langcode' => $language_interface->getId(), 'sanitize' => FALSE));
      $this->assertEqual($output, $expected, format_string('Unsanitized file token %token replaced.', array('%token' => $input)));
    }
  }
}
