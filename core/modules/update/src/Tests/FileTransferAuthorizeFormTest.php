<?php

namespace Drupal\update\Tests;

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
  public static $modules = array('update', 'update_test');

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer modules', 'administer software updates', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    // Create a local cache so the module is not downloaded from drupal.org.
    $cache_directory = _update_manager_cache_directory(TRUE);
    $validArchiveFile = __DIR__ . '/../../tests/update_test_new_module/8.x-1.0/update_test_new_module.tar.gz';
    copy($validArchiveFile, $cache_directory . '/update_test_new_module.tar.gz');
  }

  /**
   * Tests the Update Manager module upload via authorize.php functionality.
   */
  public function testViaAuthorize() {
    // Ensure the that we can select which file transfer backend to use.
    \Drupal::state()->set('test_uploaders_via_prompt', TRUE);

    // Ensure the module does not already exist.
    $this->drupalGet('admin/modules');
    $this->assertNoText('Update test new module');

    $edit = [
      // This project has been cached in the test's setUp() method.
      'project_url' => 'https://ftp.drupal.org/files/projects/update_test_new_module.tar.gz',
    ];
    $this->drupalPostForm('admin/modules/install', $edit, t('Install'));
    $edit = [
      'connection_settings[authorize_filetransfer_default]' => 'system_test',
      'connection_settings[system_test][update_test_username]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Continue'));
    $this->assertText(t('Installation was completed successfully.'));

    // Ensure the module is available to install.
    $this->drupalGet('admin/modules');
    $this->assertText('Update test new module');
  }

}
