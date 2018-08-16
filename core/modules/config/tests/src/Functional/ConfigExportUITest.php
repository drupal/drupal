<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Archiver\Tar;
use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for exporting configuration.
 *
 * @group config
 */
class ConfigExportUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['config', 'config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
  public function testExport() {
    // Verify the export page with export submit button is available.
    $this->drupalGet('admin/config/development/configuration/full/export');
    $this->assertFieldById('edit-submit', t('Export'));

    // Submit the export form and verify response. This will create a file in
    // temporary directory with the default name config.tar.gz.
    $this->drupalPostForm('admin/config/development/configuration/full/export', [], t('Export'));
    $this->assertResponse(200, 'User can access the download callback.');

    // Test if header contains file name with hostname and timestamp.
    $request = \Drupal::request();
    $hostname = str_replace('.', '-', $request->getHttpHost());
    $header_content_disposition = $this->drupalGetHeader('content-disposition');
    $header_match = (boolean) preg_match('/attachment; filename="config-' . preg_quote($hostname) . '-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}\.tar\.gz"/', $header_content_disposition);
    $this->assertTrue($header_match, "Header with filename matches the expected format.");

    // Extract the archive and verify it's not empty.
    $file_path = file_directory_temp() . '/' . 'config.tar.gz';
    $archiver = new Tar($file_path);
    $archive_contents = $archiver->listContents();
    $this->assert(!empty($archive_contents), 'Downloaded archive file is not empty.');

    // Prepare the list of config files from active storage, see
    // \Drupal\config\Controller\ConfigController::downloadExport().
    $storage_active = $this->container->get('config.storage');
    $config_files = [];
    foreach ($storage_active->listAll() as $config_name) {
      $config_files[] = $config_name . '.yml';
    }
    // Assert that the downloaded archive file contents are the same as the test
    // site active store.
    $this->assertIdentical($archive_contents, $config_files);

    // Ensure the test configuration override is in effect but was not exported.
    $this->assertIdentical(\Drupal::config('system.maintenance')->get('message'), 'Foo');
    $archiver->extract(file_directory_temp(), ['system.maintenance.yml']);
    $file_contents = file_get_contents(file_directory_temp() . '/' . 'system.maintenance.yml');
    $exported = Yaml::decode($file_contents);
    $this->assertNotIdentical($exported['message'], 'Foo');

    // Check the single export form doesn't have "form-required" elements.
    $this->drupalGet('admin/config/development/configuration/single/export');
    $this->assertNoRaw('js-form-required form-required', 'No form required fields are found.');

    // Ensure the temporary file is not available to users without the
    // permission.
    $this->drupalLogout();
    $this->drupalGet('system/temporary', ['query' => ['file' => 'config.tar.gz']]);
    $this->assertResponse(403);
  }

}
