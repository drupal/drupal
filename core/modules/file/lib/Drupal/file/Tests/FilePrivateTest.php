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
  public static function getInfo() {
    return array(
      'name' => 'Private file test',
      'description' => 'Uploads a test to a private node and checks access.',
      'group' => 'File',
    );
  }

  function setUp() {
    parent::setUp('node_access_test');
    node_access_rebuild();
    variable_set('node_access_test_private', TRUE);
  }

  /**
   * Tests file access for file uploaded to a private node.
   */
  function testPrivateFile() {
    // Use 'page' instead of 'article', so that the 'article' image field does
    // not conflict with this test. If in the future the 'page' type gets its
    // own default file or image field, this test can be made more robust by
    // using a custom node type.
    $type_name = 'page';
    $field_name = strtolower($this->randomName());
    $this->createFileField($field_name, $type_name, array('uri_scheme' => 'private'));

    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name, TRUE, array('private' => TRUE));
    $node = node_load($nid, NULL, TRUE);
    $node_file = (object) $node->{$field_name}[LANGUAGE_NOT_SPECIFIED][0];
    // Ensure the file can be downloaded.
    $this->drupalGet(file_create_url($node_file->uri));
    $this->assertResponse(200, t('Confirmed that the generated URL is correct by downloading the shipped file.'));
    $this->drupalLogOut();
    $this->drupalGet(file_create_url($node_file->uri));
    $this->assertResponse(403, t('Confirmed that access is denied for the file without the needed permission.'));
  }
}
