<?php

namespace Drupal\Tests\file\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;

/**
 * Tests file field validation functions.
 *
 * Values validated include the file type, max file size, max size per node,
 * and whether the field is required.
 *
 * @group file
 */
class FileFieldValidateTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the required property on file fields.
   */
  public function testRequired() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $storage = $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);

    $test_file = $this->getTestFile('text');

    // Try to post a new node without uploading a file.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalGet('node/add/' . $type_name);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("{$field->getLabel()} field is required.");

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertNotFalse($nid, "uploadNodeFile({$test_file->getFileUri()}, $field_name, $type_name) succeeded");

    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading to the required field.');

    // Try again with a multiple value field.
    $storage->delete();
    $this->createFileField($field_name, 'node', $type_name, ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED], ['required' => '1']);

    // Try to post a new node without uploading a file in the multivalue field.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalGet('node/add/' . $type_name);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("{$field->getLabel()} field is required.");

    // Create a new node with the uploaded file into the multivalue field.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading to the required multiple value field.');
  }

  /**
   * Tests the max file size validator.
   */
  public function testFileMaxSize() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);

    // 128KB.
    $small_file = $this->getTestFile('text', 131072);
    // 1.2MB
    $large_file = $this->getTestFile('text', 1310720);

    // Test uploading both a large and small file with different increments.
    $sizes = [
      '1M' => 1048576,
      '1024K' => 1048576,
      '1048576' => 1048576,
    ];

    foreach ($sizes as $max_filesize => $file_limit) {
      // Set the max file upload size.
      $this->updateFileField($field_name, $type_name, ['max_filesize' => $max_filesize]);

      // Create a new node with the small file, which should pass.
      $nid = $this->uploadNodeFile($small_file, $field_name, $type_name);
      $node_storage->resetCache([$nid]);
      $node = $node_storage->load($nid);
      $node_file = File::load($node->{$field_name}->target_id);
      $this->assertFileExists($node_file->getFileUri());
      $this->assertFileEntryExists($node_file, sprintf('File entry exists after uploading a file (%s) under the max limit (%s).', ByteSizeMarkup::create($small_file->getSize()), $max_filesize));

      // Check that uploading the large file fails (1M limit).
      $this->uploadNodeFile($large_file, $field_name, $type_name);
      $filesize = ByteSizeMarkup::create($large_file->getSize());
      $maxsize = ByteSizeMarkup::create($file_limit);
      $this->assertSession()->pageTextContains("The file is {$filesize} exceeding the maximum file size of {$maxsize}.");
    }

    // Turn off the max filesize.
    $this->updateFileField($field_name, $type_name, ['max_filesize' => '']);

    // Upload the big file successfully.
    $nid = $this->uploadNodeFile($large_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertFileEntryExists($node_file, sprintf('File entry exists after uploading a file (%s) with no max limit.', ByteSizeMarkup::create($large_file->getSize())));
  }

  /**
   * Tests file extension checking.
   */
  public function testFileExtension() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('image');
    [, $test_file_extension] = explode('.', $test_file->getFilename());

    // Disable extension checking.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => '']);

    // Check that the file can be uploaded with no extension checking.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading a file with no extension checking.');

    // Enable extension checking for text files.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => 'txt']);

    // Check that the file with the wrong extension cannot be uploaded.
    $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertSession()->pageTextContains("Only files with the following extensions are allowed: txt.");

    // Enable extension checking for text and image files.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => "txt $test_file_extension"]);

    // Check that the file can be uploaded with extension checking.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading a file with extension checking.');
  }

  /**
   * Checks that a file can always be removed if it does not pass validation.
   */
  public function testFileRemoval() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = 'file_test';
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('image');

    // Disable extension checking.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => '']);

    // Check that the file can be uploaded with no extension checking.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file->getFileUri());
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading a file with no extension checking.');

    // Enable extension checking for text files.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => 'txt']);

    // Check that the file can still be removed.
    $this->removeNodeFile($nid);
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed: txt.');
    $this->assertSession()->pageTextContains('Article ' . $node->getTitle() . ' has been updated.');
  }

}
