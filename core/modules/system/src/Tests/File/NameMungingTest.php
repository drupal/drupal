<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\NameMungingTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests filename munging and unmunging.
 *
 * @group File
 */
class NameMungingTest extends FileTestBase {

  /**
   * @var string
   */
  protected $badExtension;

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $nameWithUcExt;

  protected function setUp() {
    parent::setUp();
    $this->badExtension = 'php';
    $this->name = $this->randomMachineName() . '.' . $this->badExtension . '.txt';
    $this->nameWithUcExt = $this->randomMachineName() . '.' . strtoupper($this->badExtension) . '.txt';
  }

  /**
   * Create a file and munge/unmunge the name.
   */
  function testMunging() {
    // Disable insecure uploads.
    $this->config('system.file')->set('allow_insecure_uploads', 0)->save();
    $munged_name = file_munge_filename($this->name, '', TRUE);
    $messages = drupal_get_messages();
    $this->assertTrue(in_array(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $munged_name)), $messages['status']), 'Alert properly set when a file is renamed.');
    $this->assertNotEqual($munged_name, $this->name, format_string('The new filename (%munged) has been modified from the original (%original)', array('%munged' => $munged_name, '%original' => $this->name)));
  }

  /**
   * Tests munging with a null byte in the filename.
   */
  function testMungeNullByte() {
    $prefix = $this->randomMachineName();
    $filename = $prefix . '.' . $this->badExtension . "\0.txt";
    $this->assertEqual(file_munge_filename($filename, ''), $prefix . '.' . $this->badExtension . '_.txt', 'A filename with a null byte is correctly munged to remove the null byte.');
  }

  /**
   * If the system.file.allow_insecure_uploads setting evaluates to true, the file should
   * come out untouched, no matter how evil the filename.
   */
  function testMungeIgnoreInsecure() {
    $this->config('system.file')->set('allow_insecure_uploads', 1)->save();
    $munged_name = file_munge_filename($this->name, '');
    $this->assertIdentical($munged_name, $this->name, format_string('The original filename (%original) matches the munged filename (%munged) when insecure uploads are enabled.', array('%munged' => $munged_name, '%original' => $this->name)));
  }

  /**
   * White listed extensions are ignored by file_munge_filename().
   */
  function testMungeIgnoreWhitelisted() {
    // Declare our extension as whitelisted. The declared extensions should
    // be case insensitive so test using one with a different case.
    $munged_name = file_munge_filename($this->nameWithUcExt, $this->badExtension);
    $this->assertIdentical($munged_name, $this->nameWithUcExt, format_string('The new filename (%munged) matches the original (%original) once the extension has been whitelisted.', array('%munged' => $munged_name, '%original' => $this->nameWithUcExt)));
    // The allowed extensions should also be normalized.
    $munged_name = file_munge_filename($this->name, strtoupper($this->badExtension));
    $this->assertIdentical($munged_name, $this->name, format_string('The new filename (%munged) matches the original (%original) also when the whitelisted extension is in uppercase.', array('%munged' => $munged_name, '%original' => $this->name)));
  }

  /**
   * Ensure that unmunge gets your name back.
   */
  function testUnMunge() {
    $munged_name = file_munge_filename($this->name, '', FALSE);
    $unmunged_name = file_unmunge_filename($munged_name);
    $this->assertIdentical($unmunged_name, $this->name, format_string('The unmunged (%unmunged) filename matches the original (%original)', array('%unmunged' => $unmunged_name, '%original' => $this->name)));
  }
}
