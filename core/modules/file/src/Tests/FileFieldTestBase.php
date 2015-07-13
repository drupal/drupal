<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileFieldTestBase.
 */

namespace Drupal\file\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\FileInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\file\Entity\File;

/**
 * Provides methods specifically for testing File module's field handling.
 */
abstract class FileFieldTestBase extends WebTestBase {

  /**
  * Modules to enable.
  *
  * @var array
  */
  public static $modules = array('node', 'file', 'file_module_test', 'field_ui');

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer users', 'administer permissions', 'administer content types', 'administer node fields', 'administer node display', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
  }

  /**
   * Retrieves a sample file of the specified type.
   */
  function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
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
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  function createFileField($name, $entity_type, $bundle, $storage_settings = array(), $field_settings = array(), $widget_settings = array()) {
    $field_storage = entity_create('field_storage_config', array(
      'entity_type' => $entity_type,
      'field_name' => $name,
      'type' => 'file',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ));
    $field_storage->save();

    $this->attachFileField($name, $entity_type, $bundle, $field_settings, $widget_settings);
    return $field_storage;
  }

  /**
   * Attaches a file field to an entity.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type this field will be added to.
   * @param string $bundle
   *   The bundle this field will be added to.
   * @param array $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  function attachFileField($name, $entity_type, $bundle, $field_settings = array(), $widget_settings = array()) {
    $field = array(
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    );
    entity_create('field_config', $field)->save();

    entity_get_form_display($entity_type, $bundle, 'default')
      ->setComponent($name, array(
        'type' => 'file_generic',
        'settings' => $widget_settings,
      ))
      ->save();
    // Assign display settings.
    entity_get_display($entity_type, $bundle, 'default')
      ->setComponent($name, array(
        'label' => 'hidden',
        'type' => 'file_default',
      ))
      ->save();
  }

  /**
   * Updates an existing file field with new settings.
   */
  function updateFileField($name, $type_name, $field_settings = array(), $widget_settings = array()) {
    $field = FieldConfig::loadByName('node', $type_name, $name);
    $field->setSettings(array_merge($field->getSettings(), $field_settings));
    $field->save();

    entity_get_form_display('node', $type_name, 'default')
      ->setComponent($name, array(
        'settings' => $widget_settings,
      ))
      ->save();
  }

  /**
   * Uploads a file to a node.
   */
  function uploadNodeFile($file, $field_name, $nid_or_type, $new_revision = TRUE, $extras = array()) {
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'revision' => (string) (int) $new_revision,
    );

    if (is_numeric($nid_or_type)) {
      $nid = $nid_or_type;
    }
    else {
      $node_storage = $this->container->get('entity.manager')->getStorage('node');
      // Add a new node.
      $extras['type'] = $nid_or_type;
      $node = $this->drupalCreateNode($extras);
      $nid = $node->id();
      // Save at least one revision to better simulate a real site.
      $node->setNewRevision();
      $node->save();
      $node_storage->resetCache(array($nid));
      $node = $node_storage->load($nid);
      $this->assertNotEqual($nid, $node->getRevisionId(), 'Node revision exists.');
    }

    // Attach a file to the node.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $name = 'files[' . $field_name . '_0]';
    if ($field_storage->getCardinality() != 1) {
      $name .= '[]';
    }
    $edit[$name] = drupal_realpath($file->getFileUri());
    $this->drupalPostForm("node/$nid/edit", $edit, t('Save and keep published'));

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

    $this->drupalPostForm('node/' . $nid . '/edit', array(), t('Remove'));
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
  }

  /**
   * Replaces a file within a node.
   */
  function replaceNodeFile($file, $field_name, $nid, $new_revision = TRUE) {
    $edit = array(
      'files[' . $field_name . '_0]' => drupal_realpath($file->getFileUri()),
      'revision' => (string) (int) $new_revision,
    );

    $this->drupalPostForm('node/' . $nid . '/edit', array(), t('Remove'));
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
  }

  /**
   * Asserts that a file exists physically on disk.
   */
  function assertFileExists($file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file exists on the disk.', array('%file' => $file->getFileUri()));
    $this->assertTrue(is_file($file->getFileUri()), $message);
  }

  /**
   * Asserts that a file exists in the database.
   */
  function assertFileEntryExists($file, $message = NULL) {
    $this->container->get('entity.manager')->getStorage('file')->resetCache();
    $db_file = File::load($file->id());
    $message = isset($message) ? $message : format_string('File %file exists in database at the correct path.', array('%file' => $file->getFileUri()));
    $this->assertEqual($db_file->getFileUri(), $file->getFileUri(), $message);
  }

  /**
   * Asserts that a file does not exist on disk.
   */
  function assertFileNotExists($file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file exists on the disk.', array('%file' => $file->getFileUri()));
    $this->assertFalse(is_file($file->getFileUri()), $message);
  }

  /**
   * Asserts that a file does not exist in the database.
   */
  function assertFileEntryNotExists($file, $message) {
    $this->container->get('entity.manager')->getStorage('file')->resetCache();
    $message = isset($message) ? $message : format_string('File %file exists in database at the correct path.', array('%file' => $file->getFileUri()));
    $this->assertFalse(File::load($file->id()), $message);
  }

  /**
   * Asserts that a file's status is set to permanent in the database.
   */
  function assertFileIsPermanent(FileInterface $file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file is permanent.', array('%file' => $file->getFileUri()));
    $this->assertTrue($file->isPermanent(), $message);
  }

}
