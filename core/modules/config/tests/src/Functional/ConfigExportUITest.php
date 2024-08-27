<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Archiver\Tar;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for exporting configuration.
 *
 * @group config
 */
class ConfigExportUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config', 'config_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up an override.
    $settings['config']['system.maintenance']['message'] = (object) [
      'value' => 'Foo',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalLogin($this->drupalCreateUser(['export configuration']));
  }

  /**
   * Tests export of configuration.
   */
  public function testExport(): void {
    // Verify the export page with export submit button is available.
    $this->drupalGet('admin/config/development/configuration/full/export');
    $this->assertSession()->buttonExists('Export');

    // Submit the export form and verify response. This will create a file in
    // temporary directory with the default name config.tar.gz.
    $this->drupalGet('admin/config/development/configuration/full/export');
    $this->submitForm([], 'Export');
    $this->assertSession()->statusCodeEquals(200);

    // Test if header contains file name with hostname and timestamp.
    $request = \Drupal::request();
    $hostname = str_replace('.', '-', $request->getHttpHost());
    $this->assertSession()->responseHeaderMatches('content-disposition', '/attachment; filename="config-' . preg_quote($hostname) . '-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}\.tar\.gz"/');

    // Extract the archive and verify it's not empty.
    $file_system = \Drupal::service('file_system');
    assert($file_system instanceof FileSystemInterface);
    $temp_directory = $file_system->getTempDirectory();
    $file_path = $temp_directory . '/config.tar.gz';
    $archiver = new Tar($file_path);
    $archive_contents = $archiver->listContents();
    $this->assertNotEmpty($archive_contents, 'Downloaded archive file is not empty.');

    // Prepare the list of config files from active storage, see
    // \Drupal\config\Controller\ConfigController::downloadExport().
    $storage_active = $this->container->get('config.storage');
    $config_files = [];
    foreach ($storage_active->listAll() as $config_name) {
      $config_files[] = $config_name . '.yml';
    }
    // Assert that the downloaded archive file contents are the same as the test
    // site active store.
    $this->assertSame($config_files, $archive_contents);

    // Ensure the test configuration override is in effect but was not exported.
    $this->assertSame('Foo', \Drupal::config('system.maintenance')->get('message'));
    $archiver->extract($temp_directory, ['system.maintenance.yml']);
    $file_contents = file_get_contents($temp_directory . '/' . 'system.maintenance.yml');
    $exported = Yaml::decode($file_contents);
    $this->assertNotSame('Foo', $exported['message']);

    // Check the single export form doesn't have "form-required" elements.
    $this->drupalGet('admin/config/development/configuration/single/export');
    $this->assertSession()->responseNotContains('js-form-required form-required');

    // Ensure the temporary file is not available to users without the
    // permission.
    $this->drupalLogout();
    $this->drupalGet('system/temporary', ['query' => ['file' => 'config.tar.gz']]);
    $this->assertSession()->statusCodeEquals(403);
  }

}
