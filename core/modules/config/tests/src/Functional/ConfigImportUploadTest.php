<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests importing configuration from an uploaded file.
 *
 * @group config
 */
class ConfigImportUploadTest extends BrowserTestBase {

  use TestFileCreationTrait;

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
  public static $modules = ['config'];

  protected function setUp() {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['import configuration']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests importing configuration.
   */
  public function testImport() {
    // Verify access to the config upload form.
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertResponse(200);

    // Attempt to upload a non-tar file.
    $text_file = $this->getTestFiles('text')[0];
    $edit = ['files[import_tarball]' => \Drupal::service('file_system')->realpath($text_file->uri)];
    $this->drupalPostForm('admin/config/development/configuration/full/import', $edit, t('Upload'));
    $this->assertText(t('Could not extract the contents of the tar file'));

    // Make the sync directory read-only.
    $directory = Settings::get('config_sync_directory');
    \Drupal::service('file_system')->chmod($directory, 0555);
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertRaw(t('The directory %directory is not writable.', ['%directory' => $directory]));
    // Ensure submit button for \Drupal\config\Form\ConfigImportForm is
    // disabled.
    $submit_is_disabled = $this->cssSelect('form.config-import-form input[type="submit"]:disabled');
    $this->assertTrue(count($submit_is_disabled) === 1, 'The submit button is disabled.');
  }

}
