<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\NameMungingTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests for file_munge_filename() and file_unmunge_filename().
 */
class NameMungingTest extends FileTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File naming',
      'description' => 'Test filename munging and unmunging.',
      'group' => 'File API',
    );
  }

  function setUp() {
    parent::setUp();
    $this->bad_extension = 'php';
    $this->name = $this->randomName() . '.' . $this->bad_extension . '.txt';
  }

  /**
   * Create a file and munge/unmunge the name.
   */
  function testMunging() {
    // Disable insecure uploads.
    variable_set('allow_insecure_uploads', 0);
    $munged_name = file_munge_filename($this->name, '', TRUE);
    $messages = drupal_get_messages();
    $this->assertTrue(in_array(t('For security reasons, your upload has been renamed to %filename.', array('%filename' => $munged_name)), $messages['status']), t('Alert properly set when a file is renamed.'));
    $this->assertNotEqual($munged_name, $this->name, t('The new filename (%munged) has been modified from the original (%original)', array('%munged' => $munged_name, '%original' => $this->name)));
  }

  /**
   * If the allow_insecure_uploads variable evaluates to true, the file should
   * come out untouched, no matter how evil the filename.
   */
  function testMungeIgnoreInsecure() {
    variable_set('allow_insecure_uploads', 1);
    $munged_name = file_munge_filename($this->name, '');
    $this->assertIdentical($munged_name, $this->name, t('The original filename (%original) matches the munged filename (%munged) when insecure uploads are enabled.', array('%munged' => $munged_name, '%original' => $this->name)));
  }

  /**
   * White listed extensions are ignored by file_munge_filename().
   */
  function testMungeIgnoreWhitelisted() {
    // Declare our extension as whitelisted.
    $munged_name = file_munge_filename($this->name, $this->bad_extension);
    $this->assertIdentical($munged_name, $this->name, t('The new filename (%munged) matches the original (%original) once the extension has been whitelisted.', array('%munged' => $munged_name, '%original' => $this->name)));
  }

  /**
   * Ensure that unmunge gets your name back.
   */
  function testUnMunge() {
    $munged_name = file_munge_filename($this->name, '', FALSE);
    $unmunged_name = file_unmunge_filename($munged_name);
    $this->assertIdentical($unmunged_name, $this->name, t('The unmunged (%unmunged) filename matches the original (%original)', array('%unmunged' => $unmunged_name, '%original' => $this->name)));
  }
}
