<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;

/**
 * File saving tests.
 *
 * @group file
 */
class SaveTest extends FileManagedUnitTestBase {

  public function testFileSave() {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['insert']);

    $this->assertTrue($file->id() > 0, 'A new file ID is set when saving a new file to the database.', 'File');
    $loaded_file = File::load($file->id());
    $this->assertNotNull($loaded_file, 'Record exists in the database.');
    $this->assertEqual($loaded_file->isPermanent(), $file->isPermanent(), 'Status was saved correctly.');
    $this->assertEqual($file->getSize(), filesize($file->getFileUri()), 'File size was set correctly.', 'File');
    $this->assertTrue($file->getChangedTime() > 1, 'File size was set correctly.', 'File');
    $this->assertEqual($loaded_file->langcode->value, 'en', 'Langcode was defaulted correctly.');

    // Resave the file, updating the existing record.
    file_test_reset();
    $file->status->value = 7;
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['load', 'update']);

    $this->assertEqual($file->id(), $file->id(), 'The file ID of an existing file is not changed when updating the database.', 'File');
    $this->assertTrue($file->getChangedTime() >= $file->getChangedTime(), "Timestamp didn't go backwards.", 'File');
    $loaded_file = File::load($file->id());
    $this->assertNotNull($loaded_file, 'Record still exists in the database.', 'File');
    $this->assertEqual($loaded_file->isPermanent(), $file->isPermanent(), 'Status was saved correctly.');
    $this->assertEqual($loaded_file->langcode->value, 'en', 'Langcode was saved correctly.');

    // Try to insert a second file with the same name apart from case insensitivity
    // to ensure the 'uri' index allows for filenames with different cases.
    $uppercase_values = [
      'uid' => 1,
      'filename' => 'DRUPLICON.txt',
      'uri' => 'public://DRUPLICON.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ];
    $uppercase_file = File::create($uppercase_values);
    file_put_contents($uppercase_file->getFileUri(), 'hello world');
    $violations = $uppercase_file->validate();
    $this->assertCount(0, $violations, 'No violations when adding an URI with an existing filename in upper case.');
    $uppercase_file->save();

    // Ensure the database URI uniqueness constraint is triggered.
    $uppercase_file_duplicate = File::create($uppercase_values);
    file_put_contents($uppercase_file_duplicate->getFileUri(), 'hello world');
    $violations = $uppercase_file_duplicate->validate();
    $this->assertCount(1, $violations);
    $this->assertEqual($violations[0]->getMessage(), t('The file %value already exists. Enter a unique file URI.', [
      '%value' => $uppercase_file_duplicate->getFileUri(),
    ]));
    // Ensure that file URI entity queries are case sensitive.
    $fids = \Drupal::entityQuery('file')
      ->condition('uri', $uppercase_file->getFileUri())
      ->execute();

    $this->assertCount(1, $fids);
    $this->assertEqual([$uppercase_file->id() => $uppercase_file->id()], $fids);

    // Save a file with zero bytes.
    $file = File::create([
      'uid' => 1,
      'filename' => 'no-druplicon.txt',
      'uri' => 'public://no-druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);

    file_put_contents($file->getFileUri(), '');

    // Save it, inserting a new record.
    $file->save();

    // Check the file size was set to zero.
    $this->assertSame(0, $file->getSize());
  }

}
