<?php

/**
 * @file
 * Definition of Drupal\file\Tests\SaveTest.
 */

namespace Drupal\file\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests saving files.
 */
class SaveTest extends FileManagedUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File saving',
      'description' => 'File saving tests',
      'group' => 'File Managed API',
    );
  }

  function testFileSave() {
    // Create a new file entity.
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('insert'));

    $this->assertTrue($file->id() > 0, 'A new file ID is set when saving a new file to the database.', 'File');
    $loaded_file = file_load($file->id());
    $this->assertNotNull($loaded_file, 'Record exists in the database.');
    $this->assertEqual($loaded_file->isPermanent(), $file->isPermanent(), 'Status was saved correctly.');
    $this->assertEqual($file->getSize(), filesize($file->getFileUri()), 'File size was set correctly.', 'File');
    $this->assertTrue($file->getChangedTime() > 1, 'File size was set correctly.', 'File');
    $this->assertEqual($loaded_file->langcode->value, Language::LANGCODE_NOT_SPECIFIED, 'Langcode was defaulted correctly.');

    // Resave the file, updating the existing record.
    file_test_reset();
    $file->status->value = 7;
    $file->langcode = 'en';
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('load', 'update'));

    $this->assertEqual($file->id(), $file->id(), 'The file ID of an existing file is not changed when updating the database.', 'File');
    $this->assertTrue($file->getChangedTime() >= $file->getChangedTime(), "Timestamp didn't go backwards.", 'File');
    $loaded_file = file_load($file->id());
    $this->assertNotNull($loaded_file, 'Record still exists in the database.', 'File');
    $this->assertEqual($loaded_file->isPermanent(), $file->isPermanent(), 'Status was saved correctly.');
    $this->assertEqual($loaded_file->langcode->value, 'en', 'Langcode was saved correctly.');

    // Try to insert a second file with the same name apart from case insensitivity
    // to ensure the 'uri' index allows for filenames with different cases.
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => 'DRUPLICON.txt',
      'uri' => 'public://DRUPLICON.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();
  }
}
