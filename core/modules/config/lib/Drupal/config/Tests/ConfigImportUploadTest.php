<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigImportUploadTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests importing configuration from an uploaded file.
 */
class ConfigImportUploadTest extends WebTestBase {

  public static $modules = array('config');

  public static function getInfo() {
    return array(
      'name' => 'Import uploaded config',
      'description' => 'Tests importing configuration from an uploaded file.',
      'group' => 'Configuration'
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array('import configuration'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests importing configuration.
   */
  function testImport() {
    // Verify access to the config upload form.
    $this->drupalGet('admin/config/development/import');
    $this->assertResponse(200);

    // Attempt to upload a non-tar file.
    $text_file = current($this->drupalGetTestFiles('text'));
    $edit = array('files[import_tarball]' => drupal_realpath($text_file->uri));
    $this->drupalPost('admin/config/development/import', $edit, t('Upload'));
    $this->assertText(t('Could not extract the contents of the tar file'));
  }

}
