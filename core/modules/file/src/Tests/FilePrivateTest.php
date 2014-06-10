<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FilePrivateTest.
 */

namespace Drupal\file\Tests;

/**
 * Tests file access on private nodes.
 */
class FilePrivateTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node_access_test', 'field_test');

  public static function getInfo() {
    return array(
      'name' => 'Private file test',
      'description' => 'Uploads a test to a private node and checks access.',
      'group' => 'File',
    );
  }

  public function setUp() {
    parent::setUp();
    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Tests file access for file uploaded to a private node.
   */
  function testPrivateFile() {
    $type_name = 'article';
    $field_name = strtolower($this->randomName());
    $this->createFileField($field_name, 'node', $type_name, array('uri_scheme' => 'private'));

    // Create a field with no view access. See
    // field_test_entity_field_access().
    $no_access_field_name = 'field_no_view_access';
    $this->createFileField($no_access_field_name, 'node', $type_name, array('uri_scheme' => 'private'));

    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name, TRUE, array('private' => TRUE));
    $node = node_load($nid, TRUE);
    $node_file = file_load($node->{$field_name}->target_id);
    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');
    $this->drupalLogOut();
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertResponse(403, 'Confirmed that access is denied for the file without the needed permission.');

    // Test with the field that should deny access through field access.
    $this->drupalLogin($this->admin_user);
    $nid = $this->uploadNodeFile($test_file, $no_access_field_name, $type_name, TRUE, array('private' => TRUE));
    $node = node_load($nid, TRUE);
    $node_file = file_load($node->{$no_access_field_name}->target_id);
    // Ensure the file cannot be downloaded.
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertResponse(403, 'Confirmed that access is denied for the file without view field access permission.');
  }
}
