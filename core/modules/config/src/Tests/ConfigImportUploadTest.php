<?php

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests importing configuration from an uploaded file.
 *
 * @group config
 */
class ConfigImportUploadTest extends WebTestBase {

  /**
   * A user with the 'import configuration' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('config');

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(array('import configuration'));
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests importing configuration.
   */
  function testImport() {
    // Verify access to the config upload form.
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertResponse(200);

    // Attempt to upload a non-tar file.
    $text_file = current($this->drupalGetTestFiles('text'));
    $edit = array('files[import_tarball]' => drupal_realpath($text_file->uri));
    $this->drupalPostForm('admin/config/development/configuration/full/import', $edit, t('Upload'));
    $this->assertText(t('Could not extract the contents of the tar file'));

    // Make the sync directory read-only.
    $directory = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    \Drupal::service('file_system')->chmod($directory, 0555);
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertRaw(t('The directory %directory is not writable.', ['%directory' => $directory]));
    // Ensure submit button for \Drupal\config\Form\ConfigImportForm is
    // disabled.
    $submit_is_disabled = $this->cssSelect('form.config-import-form input[type="submit"]:disabled');
    $this->assertTrue(count($submit_is_disabled) === 1, 'The submit button is disabled.');
  }

}
