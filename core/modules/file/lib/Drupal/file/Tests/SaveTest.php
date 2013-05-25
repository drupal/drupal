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
class SaveTest extends FileManagedTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File saving',
      'description' => 'File saving tests',
      'group' => 'File API',
    );
  }

  function testFileSave() {
    // Create a new file entity.
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'timestamp' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->uri, 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('insert'));

    $this->assertTrue($file->fid > 0, t("A new file ID is set when saving a new file to the database."), 'File');
    $loaded_file = db_query('SELECT * FROM {file_managed} f WHERE f.fid = :fid', array(':fid' => $file->fid))->fetchObject();
    $this->assertNotNull($loaded_file, t("Record exists in the database."));
    $this->assertEqual($loaded_file->status, $file->status, t("Status was saved correctly."));
    $this->assertEqual($file->filesize, filesize($file->uri), t("File size was set correctly."), 'File');
    $this->assertTrue($file->timestamp > 1, t("File size was set correctly."), 'File');
    $this->assertEqual($loaded_file->langcode, Language::LANGCODE_NOT_SPECIFIED, t("Langcode was defaulted correctly."));

    // Resave the file, updating the existing record.
    file_test_reset();
    $file->status = 7;
    $file->langcode = 'en';
    $file->save();

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(array('load', 'update'));

    $this->assertEqual($file->fid, $file->fid, t("The file ID of an existing file is not changed when updating the database."), 'File');
    $this->assertTrue($file->timestamp >= $file->timestamp, t("Timestamp didn't go backwards."), 'File');
    $loaded_file = db_query('SELECT * FROM {file_managed} f WHERE f.fid = :fid', array(':fid' => $file->fid))->fetchObject();
    $this->assertNotNull($loaded_file, t("Record still exists in the database."), 'File');
    $this->assertEqual($loaded_file->status, $file->status, t("Status was saved correctly."));
    $this->assertEqual($loaded_file->langcode, 'en', t("Langcode was saved correctly."));

    // Try to insert a second file with the same name apart from case insensitivity
    // to ensure the 'uri' index allows for filenames with different cases.
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => 'DRUPLICON.txt',
      'uri' => 'public://DRUPLICON.txt',
      'filemime' => 'text/plain',
      'timestamp' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->uri, 'hello world');
    $file->save();
  }
}
