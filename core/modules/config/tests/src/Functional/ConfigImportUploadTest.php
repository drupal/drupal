<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  protected static $modules = ['config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser(['import configuration']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests importing configuration.
   */
  public function testImport(): void {
    // Verify access to the config upload form.
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertSession()->statusCodeEquals(200);

    // Attempt to upload a non-tar file.
    $text_file = $this->getTestFiles('text')[0];
    $edit = ['files[import_tarball]' => \Drupal::service('file_system')->realpath($text_file->uri)];
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->submitForm($edit, 'Upload');
    $this->assertSession()->pageTextContains('Could not extract the contents of the tar file');

    // Make the sync directory read-only.
    $directory = Settings::get('config_sync_directory');
    \Drupal::service('file_system')->chmod($directory, 0555);
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertSession()->pageTextContains("The directory $directory is not writable.");
    // Ensure submit button for \Drupal\config\Form\ConfigImportForm is
    // disabled.
    $submit_is_disabled = $this->cssSelect('form.config-import-form input[type="submit"]:disabled');
    $this->assertCount(1, $submit_is_disabled, 'The submit button is disabled.');
  }

  /**
   * Tests importing tarball with non-config contents.
   */
  public function testImportTarballFiltering(): void {
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->assertSession()->statusCodeEquals(200);

    $tarball = __DIR__ . '/../../fixtures/not_just_config.tar.gz';
    $edit = ['files[import_tarball]' => $tarball];
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->submitForm($edit, 'Upload');

    $sync_directory = Settings::get('config_sync_directory');
    $this->assertFileExists($sync_directory . DIRECTORY_SEPARATOR . 'config.one.yml');
    $this->assertFileExists($sync_directory . DIRECTORY_SEPARATOR . 'config.two.yml');
    $this->assertFileExists($sync_directory . DIRECTORY_SEPARATOR . 'executable.yml');
    $this->assertFalse(is_executable($sync_directory . DIRECTORY_SEPARATOR . 'executable.yml'));
    $this->assertFileDoesNotExist($sync_directory . DIRECTORY_SEPARATOR . 'script.sh');
    $this->assertFalse(is_executable($sync_directory . DIRECTORY_SEPARATOR . 'script.sh'));
  }

}
