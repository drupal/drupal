<?php

/**
 * @file
 * Definition of Drupal\update\Tests\UpdateUploadTest.
 */

namespace Drupal\update\Tests;

/**
 * Tests project upload and extract functionality.
 */
class UpdateUploadTest extends UpdateTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Upload and extract module functionality',
      'description' => 'Tests the Update Manager module\'s upload and extraction functionality.',
      'group' => 'Update',
    );
  }

  public function setUp() {
    parent::setUp('update', 'update_test');
    variable_set('allow_authorize_operations', TRUE);
    $admin_user = $this->drupalCreateUser(array('administer software updates', 'administer site configuration'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests upload and extraction of a module.
   */
  public function testUploadModule() {
    // Images are not valid archives, so get one and try to install it. We
    // need an extra variable to store the result of drupalGetTestFiles()
    // since reset() takes an argument by reference and passing in a constant
    // emits a notice in strict mode.
    $imageTestFiles = $this->drupalGetTestFiles('image');
    $invalidArchiveFile = reset($imageTestFiles);
    $edit = array(
      'files[project_upload]' => $invalidArchiveFile->uri,
    );
    // This also checks that the correct archive extensions are allowed.
    $this->drupalPost('admin/modules/install', $edit, t('Install'));
    $this->assertText(t('Only files with the following extensions are allowed: @archive_extensions.', array('@archive_extensions' => archiver_get_extensions())),'Only valid archives can be uploaded.');

    // Check to ensure an existing module can't be reinstalled. Also checks that
    // the archive was extracted since we can't know if the module is already
    // installed until after extraction.
    $validArchiveFile = drupal_get_path('module', 'update') . '/tests/aaa_update_test.tar.gz';
    $edit = array(
      'files[project_upload]' => $validArchiveFile,
    );
    $this->drupalPost('admin/modules/install', $edit, t('Install'));
    $this->assertText(t('@module_name is already installed.', array('@module_name' => 'AAA Update test')), 'Existing module was extracted and not reinstalled.');
  }

  /**
   * Ensures that archiver extensions are properly merged in the UI.
   */
  function testFileNameExtensionMerging() {
    $this->drupalGet('admin/modules/install');
    // Make sure the bogus extension supported by update_test.module is there.
    $this->assertPattern('/file extensions are supported:.*update-test-extension/', t("Found 'update-test-extension' extension"));
    // Make sure it didn't clobber the first option from core.
    $this->assertPattern('/file extensions are supported:.*tar/', t("Found 'tar' extension"));
  }

  /**
   * Checks the messages on update manager pages when missing a security update.
   */
  function testUpdateManagerCoreSecurityUpdateMessages() {
    $setting = array(
      '#all' => array(
        'version' => '7.0',
      ),
    );
    variable_set('update_test_system_info', $setting);
    variable_set('update_fetch_url', url('update-test', array('absolute' => TRUE)));
    variable_set('update_test_xml_map', array('drupal' => '2-sec'));
    // Initialize the update status.
    $this->drupalGet('admin/reports/updates');

    // Now, make sure none of the Update manager pages have duplicate messages
    // about core missing a security update.

    $this->drupalGet('admin/modules/install');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/modules/update');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/appearance/install');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/appearance/update');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/reports/updates/install');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/reports/updates/update');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/update/ready');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }
}
