<?php

/**
 * @file
 * Definition of Drupal\file\Tests\FileFieldTestBase.
 */

namespace Drupal\file\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides methods specifically for testing File module's field handling.
 */
class FileFieldTestBase extends WebTestBase {
  protected $profile = 'standard';

  protected $admin_user;

  function setUp() {
    // Since this is a base class for many test cases, support the same
    // flexibility that Drupal\simpletest\WebTestBase::setUp() has for the
    // modules to be passed in as either an array or a variable number of string
    // arguments.
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'file';
    $modules[] = 'file_module_test';
    parent::setUp($modules);
    $this->admin_user = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer users', 'administer permissions', 'administer content types', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Retrieves a sample file of the specified type.
   */
  function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by file_load().
    $file->filesize = filesize($file->uri);

    return entity_create('file', (array) $file);
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  function getLastFileId() {
    return (int) db_query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

  /**
   * Creates a new file field.
   *
   * @param $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param $type_name
   *   The node type that this field will be added to.
   * @param $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param $instance_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  function createFileField($name, $type_name, $field_settings = array(), $instance_settings = array(), $widget_settings = array()) {
    $field = array(
      'field_name' => $name,
      'type' => 'file',
      'settings' => array(),
      'cardinality' => !empty($field_settings['cardinality']) ? $field_settings['cardinality'] : 1,
    );
    $field['settings'] = array_merge($field['settings'], $field_settings);
    field_create_field($field);

    $this->attachFileField($name, 'node', $type_name, $instance_settings, $widget_settings);
  }

  /**
   * Attaches a file field to an entity.
   *
   * @param $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param $entity_type
   *   The entity type this field will be added to.
   * @param $bundle
   *   The bundle this field will be added to.
   * @param $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param $instance_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  function attachFileField($name, $entity_type, $bundle, $instance_settings = array(), $widget_settings = array()) {
    $instance = array(
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($instance_settings['required']),
      'settings' => array(),
      'widget' => array(
        'type' => 'file_generic',
        'settings' => array(),
      ),
    );
    $instance['settings'] = array_merge($instance['settings'], $instance_settings);
    $instance['widget']['settings'] = array_merge($instance['widget']['settings'], $widget_settings);
    field_create_instance($instance);
  }

  /**
   * Updates an existing file field with new settings.
   */
  function updateFileField($name, $type_name, $instance_settings = array(), $widget_settings = array()) {
    $instance = field_info_instance('node', $name, $type_name);
    $instance['settings'] = array_merge($instance['settings'], $instance_settings);
    $instance['widget']['settings'] = array_merge($instance['widget']['settings'], $widget_settings);

    field_update_instance($instance);
  }

  /**
   * Uploads a file to a node.
   */
  function uploadNodeFile($file, $field_name, $nid_or_type, $new_revision = TRUE, $extras = array()) {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $edit = array(
      "title" => $this->randomName(),
      'revision' => (string) (int) $new_revision,
    );

    if (is_numeric($nid_or_type)) {
      $nid = $nid_or_type;
    }
    else {
      // Add a new node.
      $extras['type'] = $nid_or_type;
      $node = $this->drupalCreateNode($extras);
      $nid = $node->nid;
      // Save at least one revision to better simulate a real site.
      $this->drupalCreateNode(get_object_vars($node));
      $node = node_load($nid, NULL, TRUE);
      $this->assertNotEqual($nid, $node->vid, t('Node revision exists.'));
    }

    // Attach a file to the node.
    $edit['files[' . $field_name . '_' . $langcode . '_0]'] = drupal_realpath($file->uri);
    $this->drupalPost("node/$nid/edit", $edit, t('Save'));

    return $nid;
  }

  /**
   * Removes a file from a node.
   *
   * Note that if replacing a file, it must first be removed then added again.
   */
  function removeNodeFile($nid, $new_revision = TRUE) {
    $edit = array(
      'revision' => (string) (int) $new_revision,
    );

    $this->drupalPost('node/' . $nid . '/edit', array(), t('Remove'));
    $this->drupalPost(NULL, $edit, t('Save'));
  }

  /**
   * Replaces a file within a node.
   */
  function replaceNodeFile($file, $field_name, $nid, $new_revision = TRUE) {
    $edit = array(
      'files[' . $field_name . '_' . LANGUAGE_NOT_SPECIFIED . '_0]' => drupal_realpath($file->uri),
      'revision' => (string) (int) $new_revision,
    );

    $this->drupalPost('node/' . $nid . '/edit', array(), t('Remove'));
    $this->drupalPost(NULL, $edit, t('Save'));
  }

  /**
   * Asserts that a file exists physically on disk.
   */
  function assertFileExists($file, $message = NULL) {
    $message = isset($message) ? $message : t('File %file exists on the disk.', array('%file' => $file->uri));
    $this->assertTrue(is_file($file->uri), $message);
  }

  /**
   * Asserts that a file exists in the database.
   */
  function assertFileEntryExists($file, $message = NULL) {
    entity_get_controller('file')->resetCache();
    $db_file = file_load($file->fid);
    $message = isset($message) ? $message : t('File %file exists in database at the correct path.', array('%file' => $file->uri));
    $this->assertEqual($db_file->uri, $file->uri, $message);
  }

  /**
   * Asserts that a file does not exist on disk.
   */
  function assertFileNotExists($file, $message = NULL) {
    $message = isset($message) ? $message : t('File %file exists on the disk.', array('%file' => $file->uri));
    $this->assertFalse(is_file($file->uri), $message);
  }

  /**
   * Asserts that a file does not exist in the database.
   */
  function assertFileEntryNotExists($file, $message) {
    entity_get_controller('file')->resetCache();
    $message = isset($message) ? $message : t('File %file exists in database at the correct path.', array('%file' => $file->uri));
    $this->assertFalse(file_load($file->fid), $message);
  }

  /**
   * Asserts that a file's status is set to permanent in the database.
   */
  function assertFileIsPermanent($file, $message = NULL) {
    $message = isset($message) ? $message : t('File %file is permanent.', array('%file' => $file->uri));
    $this->assertTrue($file->status == FILE_STATUS_PERMANENT, $message);
  }
}
