<?php

namespace Drupal\Tests\update\Functional;

/**
 * Tests the Update Manager module upload via authorize.php functionality.
 *
 * @group update
 */
class FileTransferAuthorizeFormTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['update', 'update_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer software updates',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // Create a local cache so the module is not downloaded from drupal.org.
    $cache_directory = _update_manager_cache_directory(TRUE);
    foreach (['.tar.gz', '.zip'] as $extension) {
      $filename = 'update_test_new_module' . $extension;
      copy(
        __DIR__ . '/../../update_test_new_module/8.x-1.0/' . $filename,
        $cache_directory . '/' . $filename
      );
    }
  }

  /**
   * Tests the Update Manager module upload via authorize.php functionality.
   *
   * @dataProvider archiveFileUrlProvider
   */
  public function testViaAuthorize($url) {
    // Ensure the that we can select which file transfer backend to use.
    \Drupal::state()->set('test_uploaders_via_prompt', TRUE);

    // Ensure the module does not already exist.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains('Update test new module');

    $edit = [
      'project_url' => $url,
    ];
    $this->drupalGet('admin/modules/install');
    $this->submitForm($edit, 'Continue');
    $edit = [
      'connection_settings[authorize_filetransfer_default]' => 'system_test',
      'connection_settings[system_test][update_test_username]' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, 'Continue');
    $this->assertSession()->pageTextContains('Files were added successfully.');

    // Ensure the module is available to install.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('Update test new module');
  }

  /**
   * Data provider method for testViaAuthorize().
   *
   * Each of these release URLs has been cached in the setUp() method.
   */
  public function archiveFileUrlProvider() {
    return [
      'tar.gz' => [
        'url' => 'https://ftp.drupal.org/files/projects/update_test_new_module.tar.gz',
      ],
      'zip' => [
        'url' => 'https://ftp.drupal.org/files/projects/update_test_new_module.zip',
      ],
    ];
  }

}
