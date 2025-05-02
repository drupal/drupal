<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\FileInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Provides methods specifically for testing File module's field handling.
 */
abstract class FileFieldTestBase extends BrowserTestBase {

  use FileFieldCreationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'file_module_test', 'field_ui'];

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer users',
      'administer permissions',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   *   The new unsaved file entity.
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
    return (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
  }

  /**
   * Updates an existing file field with new settings.
   */
  public function updateFileField($name, $type_name, $field_settings = [], $widget_settings = []) {
    $field = FieldConfig::loadByName('node', $type_name, $name);
    $field->setSettings(array_merge($field->getSettings(), $field_settings));
    $field->save();

    \Drupal::service('entity_display.repository')->getFormDisplay('node', $type_name)
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
   * @param int|string $nid_or_type
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
   * @param int|string $nid_or_type
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

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    if (is_numeric($nid_or_type)) {
      $nid = $nid_or_type;
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
      $node = $node_storage->load($nid);
      $this->assertNotEquals($nid, $node->getRevisionId(), 'Node revision exists.');
    }
    $this->drupalGet("node/$nid/edit");
    $page = $this->getSession()->getPage();

    // Attach files to the node.
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    // File input name depends on number of files already uploaded.
    $field_num = count($node->{$field_name});
    foreach ($files as $i => $file) {
      $delta = $field_num + $i;
      $file_path = $this->container->get('file_system')->realpath($file->getFileUri());
      $name = 'files[' . $field_name . '_' . $delta . ']';
      if ($field_storage->getCardinality() != 1) {
        $name .= '[]';
      }
      if (count($files) == 1) {
        $edit[$name] = $file_path;
      }
      else {
        $page->attachFileToField($name, $file_path);
        $this->submitForm([], 'Upload');
      }
    }

    $this->submitForm($edit, 'Save');

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

    $this->drupalGet('node/' . $nid . '/edit');
    $this->submitForm([], 'Remove');
    $this->submitForm($edit, 'Save');
  }

  /**
   * Replaces a file within a node.
   */
  public function replaceNodeFile($file, $field_name, $nid, $new_revision = TRUE) {
    $edit = [
      'files[' . $field_name . '_0]' => \Drupal::service('file_system')->realpath($file->getFileUri()),
      'revision' => (string) (int) $new_revision,
    ];

    $this->drupalGet('node/' . $nid . '/edit');
    $this->submitForm([], 'Remove');
    $this->submitForm($edit, 'Save');
  }

  /**
   * Asserts that a file exists in the database.
   */
  public function assertFileEntryExists($file, $message = NULL) {
    $this->container->get('entity_type.manager')->getStorage('file')->resetCache();
    $db_file = File::load($file->id());
    $message = $message ?? sprintf('File %s exists in database at the correct path.', $file->getFileUri());
    $this->assertEquals($file->getFileUri(), $db_file->getFileUri(), $message);
  }

  /**
   * Asserts that a file does not exist in the database.
   */
  public function assertFileEntryNotExists($file, $message) {
    $this->container->get('entity_type.manager')->getStorage('file')->resetCache();
    $message = $message ?? sprintf('File %s exists in database at the correct path.', $file->getFileUri());
    $this->assertNull(File::load($file->id()), $message);
  }

  /**
   * Asserts that a file's status is set to permanent in the database.
   */
  public function assertFileIsPermanent(FileInterface $file, $message = NULL) {
    $message = $message ?? sprintf('File %s is permanent.', $file->getFileUri());
    $this->assertTrue($file->isPermanent(), $message);
  }

}
