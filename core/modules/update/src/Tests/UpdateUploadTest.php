<?php

/**
 * @file
 * Contains \Drupal\update\Tests\UpdateUploadTest.
 */

namespace Drupal\update\Tests;

use Drupal\Core\Updater\Updater;
use Drupal\Core\Url;

/**
 * Tests the Update Manager module's upload and extraction functionality.
 *
 * @group update
 */
class UpdateUploadTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update', 'update_test');

  protected function setUp() {
    parent::setUp();
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
    $this->drupalPostForm('admin/modules/install', $edit, t('Install'));
    $this->assertText(t('Only files with the following extensions are allowed: @archive_extensions.', array('@archive_extensions' => archiver_get_extensions())),'Only valid archives can be uploaded.');
    $this->assertUrl('admin/modules/install');

    // Check to ensure an existing module can't be reinstalled. Also checks that
    // the archive was extracted since we can't know if the module is already
    // installed until after extraction.
    $validArchiveFile = drupal_get_path('module', 'update') . '/tests/aaa_update_test.tar.gz';
    $edit = array(
      'files[project_upload]' => $validArchiveFile,
    );
    $this->drupalPostForm('admin/modules/install', $edit, t('Install'));
    $this->assertText(t('@module_name is already installed.', array('@module_name' => 'AAA Update test')), 'Existing module was extracted and not reinstalled.');
    $this->assertUrl('admin/modules/install');

    // Ensure that a new module can be extracted and installed.
    $updaters = drupal_get_updaters();
    $moduleUpdater = $updaters['module']['class'];
    $installedInfoFilePath = $this->container->get('update.root') . '/' . $moduleUpdater::getRootDirectoryRelativePath() . '/update_test_new_module/update_test_new_module.info.yml';
    $this->assertFalse(file_exists($installedInfoFilePath), 'The new module does not exist in the filesystem before it is installed with the Update Manager.');
    $validArchiveFile = drupal_get_path('module', 'update') . '/tests/update_test_new_module.tar.gz';
    $edit = array(
      'files[project_upload]' => $validArchiveFile,
    );
    $this->drupalPostForm('admin/modules/install', $edit, t('Install'));
    // Check that submitting the form takes the user to authorize.php.
    $this->assertUrl('core/authorize.php');
    // Check for a success message on the page, and check that the installed
    // module now exists in the expected place in the filesystem.
    $this->assertRaw(t('Installed %project_name successfully', array('%project_name' => 'update_test_new_module')));
    $this->assertTrue(file_exists($installedInfoFilePath), 'The new module exists in the filesystem after it is installed with the Update Manager.');
    // Ensure the links are relative to the site root and not
    // core/authorize.php.
    $this->assertLink(t('Install another module'));
    $this->assertLinkByHref(Url::fromRoute('update.module_install')->toString());
    $this->assertLink(t('Enable newly added modules'));
    $this->assertLinkByHref(Url::fromRoute('system.modules_list')->toString());
    $this->assertLink(t('Administration pages'));
    $this->assertLinkByHref(Url::fromRoute('system.admin')->toString());
    // Ensure we can reach the "Install another module" link.
    $this->clickLink(t('Install another module'));
    $this->assertResponse(200);
    $this->assertUrl('admin/modules/install');
  }

  /**
   * Ensures that archiver extensions are properly merged in the UI.
   */
  function testFileNameExtensionMerging() {
    $this->drupalGet('admin/modules/install');
    // Make sure the bogus extension supported by update_test.module is there.
    $this->assertPattern('/file extensions are supported:.*update-test-extension/', "Found 'update-test-extension' extension.");
    // Make sure it didn't clobber the first option from core.
    $this->assertPattern('/file extensions are supported:.*tar/', "Found 'tar' extension.");
  }

  /**
   * Checks the messages on update manager pages when missing a security update.
   */
  function testUpdateManagerCoreSecurityUpdateMessages() {
    $setting = array(
      '#all' => array(
        'version' => '8.0.0',
      ),
    );
    $this->config('update_test.settings')
      ->set('system_info', $setting)
      ->set('xml_map', array('drupal' => '0.2-sec'))
      ->save();
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
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

  /**
   * Tests only an *.info.yml file are detected without supporting files.
   */
  public function testUpdateDirectory() {
    $type = Updater::getUpdaterFromDirectory(\Drupal::root() . '/core/modules/update/tests/modules/aaa_update_test');
    $this->assertEqual($type, 'Drupal\\Core\\Updater\\Module', 'Detected a Module');

    $type = Updater::getUpdaterFromDirectory(\Drupal::root() . '/core/modules/update/tests/themes/update_test_basetheme');
    $this->assertEqual($type, 'Drupal\\Core\\Updater\\Theme', 'Detected a Theme.');
  }

}
