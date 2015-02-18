<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigExportUITest.
 */

namespace Drupal\config\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\Tar;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the user interface for exporting configuration.
 *
 * @group config
 */
class ConfigExportUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config', 'config_test', 'config_export_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(array('export configuration')));
  }

  /**
   * Tests export of configuration.
   */
  function testExport() {
    // Verify the export page with export submit button is available.
    $this->drupalGet('admin/config/development/configuration/full/export');
    $this->assertFieldById('edit-submit', t('Export'));

    // Submit the export form and verify response.
    $this->drupalPostForm('admin/config/development/configuration/full/export', array(), t('Export'));
    $this->assertResponse(200, 'User can access the download callback.');

    // Get the archived binary file provided to user for download.
    $archive_data = $this->getRawContent();

    // Temporarily save the archive file.
    $uri = file_unmanaged_save_data($archive_data, 'temporary://config.tar.gz');

    // Extract the archive and verify it's not empty.
    $file_path = file_directory_temp() . '/' . file_uri_target($uri);
    $archiver = new Tar($file_path);
    $archive_contents = $archiver->listContents();
    $this->assert(!empty($archive_contents), 'Downloaded archive file is not empty.');

    // Prepare the list of config files from active storage, see
    // \Drupal\config\Controller\ConfigController::downloadExport().
    $storage_active = $this->container->get('config.storage');
    $config_files = array();
    foreach ($storage_active->listAll() as $config_name) {
      $config_files[] = $config_name . '.yml';
    }
    // Assert that the downloaded archive file contents are the same as the test
    // site active store.
    $this->assertIdentical($archive_contents, $config_files);

    // Ensure the test configuration override is in effect but was not exported.
    $this->assertIdentical(\Drupal::config('system.maintenance')->get('message'), 'Foo');
    $archiver->extract(file_directory_temp(), array('system.maintenance.yml'));
    $file_contents = file_get_contents(file_directory_temp() . '/' . 'system.maintenance.yml');
    $exported = Yaml::decode($file_contents);
    $this->assertNotIdentical($exported['message'], 'Foo');
  }

}
