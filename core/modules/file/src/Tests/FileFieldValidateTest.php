<?php

namespace Drupal\file\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;

/**
 * Tests validation functions such as file type, max file size, max size per
 * node, and required.
 *
 * @group file
 */
class FileFieldValidateTest extends FileFieldTestBase {

  /**
   * Tests the required property on file fields.
   */
  public function testRequired() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $storage = $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);

    $test_file = $this->getTestFile('text');

    // Try to post a new node without uploading a file.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/' . $type_name, $edit, t('Save'));
    $this->assertRaw(t('@title field is required.', ['@title' => $field->getLabel()]), 'Node save failed when required file field was empty.');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->assertTrue($nid !== FALSE, format_string('uploadNodeFile(@test_file, @field_name, @type_name) succeeded', ['@test_file' => $test_file->getFileUri(), '@field_name' => $field_name, '@type_name' => $type_name]));

    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'File exists after uploading to the required field.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading to the required field.');

    // Try again with a multiple value field.
    $storage->delete();
    $this->createFileField($field_name, 'node', $type_name, ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED], ['required' => '1']);

    // Try to post a new node without uploading a file in the multivalue field.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm('node/add/' . $type_name, $edit, t('Save'));
    $this->assertRaw(t('@title field is required.', ['@title' => $field->getLabel()]), 'Node save failed when required multiple value file field was empty.');

    // Create a new node with the uploaded file into the multivalue field.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'File exists after uploading to the required multiple value field.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading to the required multiple value field.');
  }

  /**
   * Tests the max file size validator.
   */
  public function testFileMaxSize() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name, [], ['required' => '1']);

    $small_file = $this->getTestFile('text', 131072); // 128KB.
    $large_file = $this->getTestFile('text', 1310720); // 1.2MB

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
      $this->assertFileExists($node_file, format_string('File exists after uploading a file (%filesize) under the max limit (%maxsize).', ['%filesize' => format_size($small_file->getSize()), '%maxsize' => $max_filesize]));
      $this->assertFileEntryExists($node_file, format_string('File entry exists after uploading a file (%filesize) under the max limit (%maxsize).', ['%filesize' => format_size($small_file->getSize()), '%maxsize' => $max_filesize]));

      // Check that uploading the large file fails (1M limit).
      $this->uploadNodeFile($large_file, $field_name, $type_name);
      $error_message = t('The file is %filesize exceeding the maximum file size of %maxsize.', ['%filesize' => format_size($large_file->getSize()), '%maxsize' => format_size($file_limit)]);
      $this->assertRaw($error_message, format_string('Node save failed when file (%filesize) exceeded the max upload size (%maxsize).', ['%filesize' => format_size($large_file->getSize()), '%maxsize' => $max_filesize]));
    }

    // Turn off the max filesize.
    $this->updateFileField($field_name, $type_name, ['max_filesize' => '']);

    // Upload the big file successfully.
    $nid = $this->uploadNodeFile($large_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, format_string('File exists after uploading a file (%filesize) with no max limit.', ['%filesize' => format_size($large_file->getSize())]));
    $this->assertFileEntryExists($node_file, format_string('File entry exists after uploading a file (%filesize) with no max limit.', ['%filesize' => format_size($large_file->getSize())]));
  }

  /**
   * Tests file extension checking.
   */
  public function testFileExtension() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('image');
    list(, $test_file_extension) = explode('.', $test_file->getFilename());

    // Disable extension checking.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => '']);

    // Check that the file can be uploaded with no extension checking.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'File exists after uploading a file with no extension checking.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading a file with no extension checking.');

    // Enable extension checking for text files.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => 'txt']);

    // Check that the file with the wrong extension cannot be uploaded.
    $this->uploadNodeFile($test_file, $field_name, $type_name);
    $error_message = t('Only files with the following extensions are allowed: %files-allowed.', ['%files-allowed' => 'txt']);
    $this->assertRaw($error_message, 'Node save failed when file uploaded with the wrong extension.');

    // Enable extension checking for text and image files.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => "txt $test_file_extension"]);

    // Check that the file can be uploaded with extension checking.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'File exists after uploading a file with extension checking.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading a file with extension checking.');
  }

  /**
   * Checks that a file can always be removed if it does not pass validation.
   */
  public function testFileRemoval() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
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
    $this->assertFileExists($node_file, 'File exists after uploading a file with no extension checking.');
    $this->assertFileEntryExists($node_file, 'File entry exists after uploading a file with no extension checking.');

    // Enable extension checking for text files.
    $this->updateFileField($field_name, $type_name, ['file_extensions' => 'txt']);

    // Check that the file can still be removed.
    $this->removeNodeFile($nid);
    $this->assertNoText('Only files with the following extensions are allowed: txt.');
    $this->assertText('Article ' . $node->getTitle() . ' has been updated.');
  }

}
