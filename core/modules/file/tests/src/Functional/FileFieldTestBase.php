<?php

namespace Drupal\Tests\file\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\FileInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\file\Entity\File;

/**
 * Provides methods specifically for testing File module's field handling.
 */
abstract class FileFieldTestBase extends BrowserTestBase {

  use FileFieldCreationTrait;

  /**
  * Modules to enable.
  *
  * @var array
  */
  public static $modules = ['node', 'file', 'file_module_test', 'field_ui'];

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['access content', 'access administration pages', 'administer site configuration', 'administer users', 'administer permissions', 'administer content types', 'administer node fields', 'administer node display', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   */
  public function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);

    return File::create((array) $file);
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  public function getLastFileId() {
    return (int) db_query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

  /**
   * Updates an existing file field with new settings.
   */
  public function updateFileField($name, $type_name, $field_settings = [], $widget_settings = []) {
    $field = FieldConfig::loadByName('node', $type_name, $name);
    $field->setSettings(array_merge($field->getSettings(), $field_settings));
    $field->save();

    entity_get_form_display('node', $type_name, 'default')
      ->setComponent($name, [
        'settings' => $widget_settings,
      ])
      ->save();
  }

  /**
   * Uploads a file to a node.
   *
   * @param \Drupal\file\FileInterface $file
   *   The File to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param $nid_or_type
   *   A numeric node id to upload files to an existing node, or a string
   *   indicating the desired bundle for a new node.
   * @param bool $new_revision
   *   The revision number.
   * @param array $extras
   *   Additional values when a new node is created.
   *
   * @return int
   *   The node id.
   */
  public function uploadNodeFile(FileInterface $file, $field_name, $nid_or_type, $new_revision = TRUE, array $extras = []) {
    return $this->uploadNodeFiles([$file], $field_name, $nid_or_type, $new_revision, $extras);
  }

  /**
   * Uploads multiple files to a node.
   *
   * @param \Drupal\file\FileInterface[] $files
   *   The files to be uploaded.
   * @param string $field_name
   *   The name of the field on which the files should be saved.
   * @param $nid_or_type
   *   A numeric node id to upload files to an existing node, or a string
   *   indicating the desired bundle for a new node.
   * @param bool $new_revision
   *   The revision number.
   * @param array $extras
   *   Additional values when a new node is created.
   *
   * @return int
   *   The node id.
   */
  public function uploadNodeFiles(array $files, $field_name, $nid_or_type, $new_revision = TRUE, array $extras = []) {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'revision' => (string) (int) $new_revision,
    ];

    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    if (is_numeric($nid_or_type)) {
      $nid = $nid_or_type;
      $node_storage->resetCache([$nid]);
      $node = $node_storage->load($nid);
    }
    else {
      // Add a new node.
      $extras['type'] = $nid_or_type;
      $node = $this->drupalCreateNode($extras);
      $nid = $node->id();
      // Save at least one revision to better simulate a real site.
      $node->setNewRevision();
      $node->save();
      $node_storage->resetCache([$nid]);
      $node = $node_storage->load($nid);
      $this->assertNotEqual($nid, $node->getRevisionId(), 'Node revision exists.');
    }

    // Attach files to the node.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    // File input name depends on number of files already uploaded.
    $field_num = count($node->{$field_name});
    $name = 'files[' . $field_name . "_$field_num]";
    if ($field_storage->getCardinality() != 1) {
      $name .= '[]';
    }
    foreach ($files as $file) {
      $file_path = $this->container->get('file_system')->realpath($file->getFileUri());
      if (count($files) == 1) {
        $edit[$name] = $file_path;
      }
      else {
        $edit[$name][] = $file_path;
      }
    }
    $this->drupalPostForm("node/$nid/edit", $edit, t('Save and keep published'));

    return $nid;
  }

  /**
   * Removes a file from a node.
   *
   * Note that if replacing a file, it must first be removed then added again.
   */
  public function removeNodeFile($nid, $new_revision = TRUE) {
    $edit = [
      'revision' => (string) (int) $new_revision,
    ];

    $this->drupalPostForm('node/' . $nid . '/edit', [], t('Remove'));
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
  }

  /**
   * Replaces a file within a node.
   */
  public function replaceNodeFile($file, $field_name, $nid, $new_revision = TRUE) {
    $edit = [
      'files[' . $field_name . '_0]' => drupal_realpath($file->getFileUri()),
      'revision' => (string) (int) $new_revision,
    ];

    $this->drupalPostForm('node/' . $nid . '/edit', [], t('Remove'));
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
  }

  /**
   * Asserts that a file exists physically on disk.
   *
   * Overrides PHPUnit\Framework\Assert::assertFileExists() to also work with
   * file entities.
   *
   * @param \Drupal\File\FileInterface|string $file
   *   Either the file entity or the file URI.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public static function assertFileExists($file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file exists on the disk.', ['%file' => $file->getFileUri()]);
    $filename = $file instanceof FileInterface ? $file->getFileUri() : $file;
    parent::assertFileExists($filename, $message);
  }

  /**
   * Asserts that a file exists in the database.
   */
  public function assertFileEntryExists($file, $message = NULL) {
    $this->container->get('entity.manager')->getStorage('file')->resetCache();
    $db_file = File::load($file->id());
    $message = isset($message) ? $message : format_string('File %file exists in database at the correct path.', ['%file' => $file->getFileUri()]);
    $this->assertEqual($db_file->getFileUri(), $file->getFileUri(), $message);
  }

  /**
   * Asserts that a file does not exist on disk.
   *
   * Overrides PHPUnit\Framework\Assert::assertFileExists() to also work with
   * file entities.
   *
   * @param \Drupal\File\FileInterface|string $file
   *   Either the file entity or the file URI.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  public static function assertFileNotExists($file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file exists on the disk.', ['%file' => $file->getFileUri()]);
    $filename = $file instanceof FileInterface ? $file->getFileUri() : $file;
    parent::assertFileNotExists($filename, $message);
  }

  /**
   * Asserts that a file does not exist in the database.
   */
  public function assertFileEntryNotExists($file, $message) {
    $this->container->get('entity.manager')->getStorage('file')->resetCache();
    $message = isset($message) ? $message : format_string('File %file exists in database at the correct path.', ['%file' => $file->getFileUri()]);
    $this->assertFalse(File::load($file->id()), $message);
  }

  /**
   * Asserts that a file's status is set to permanent in the database.
   */
  public function assertFileIsPermanent(FileInterface $file, $message = NULL) {
    $message = isset($message) ? $message : format_string('File %file is permanent.', ['%file' => $file->getFileUri()]);
    $this->assertTrue($file->isPermanent(), $message);
  }

}
