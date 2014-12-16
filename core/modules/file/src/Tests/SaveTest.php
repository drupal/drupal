<?php

/**
 * @file
 * Definition of Drupal\file\Tests\SaveTest.
 */

namespace Drupal\file\Tests;

use Drupal\file\Entity\File;

/**
 * File saving tests.
 *
 * @group file
 */
class SaveTest extends FileManagedUnitTestBase {
  function testFileSave() {
    // Create a new file entity.
    $file = File::create(array(
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
    $this->assertEqual($loaded_file->langcode->value, 'en', 'Langcode was defaulted correctly.');

    // Resave the file, updating the existing record.
    file_test_reset();
    $file->status->value = 7;
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
    $uppercase_file = File::create(array(
      'uid' => 1,
      'filename' => 'DRUPLICON.txt',
      'uri' => 'public://DRUPLICON.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($uppercase_file->getFileUri(), 'hello world');
    $uppercase_file->save();

    // Ensure that file URI entity queries are case sensitive.
    $fids = \Drupal::entityQuery('file')
      ->condition('uri', $uppercase_file->getFileUri())
      ->execute();

    $this->assertEqual(1, count($fids));
    $this->assertEqual(array($uppercase_file->id() => $uppercase_file->id()), $fids);

  }
}
