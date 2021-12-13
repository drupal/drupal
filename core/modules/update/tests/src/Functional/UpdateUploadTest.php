<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Extension\InfoParserDynamic;
use Drupal\Core\Updater\Updater;
use Drupal\Core\Url;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the Update Manager module's upload and extraction functionality.
 *
 * @group update
 */
class UpdateUploadTest extends UpdateUploaderTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['update', 'update_test', 'file'];

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
  }

  /**
   * Tests upload, extraction, and update of a module.
   */
  public function testUploadModule() {
    // Ensure that the update information is correct before testing.
    update_get_available(TRUE);

    // Images are not valid archives, so get one and try to install it. We
    // need an extra variable to store the result of drupalGetTestFiles()
    // since reset() takes an argument by reference and passing in a constant
    // emits a notice in strict mode.
    $imageTestFiles = $this->drupalGetTestFiles('image');
    $invalidArchiveFile = reset($imageTestFiles);
    $edit = [
      'files[project_upload]' => $invalidArchiveFile->uri,
    ];
    // This also checks that the correct archive extensions are allowed.
    $this->drupalGet('admin/modules/install');
    $this->submitForm($edit, 'Continue');
    $extensions = \Drupal::service('plugin.manager.archiver')->getExtensions();
    $this->assertSession()->pageTextContains("Only files with the following extensions are allowed: $extensions.");
    $this->assertSession()->addressEquals('admin/modules/install');

    // Check to ensure an existing module can't be reinstalled. Also checks that
    // the archive was extracted since we can't know if the module is already
    // installed until after extraction.
    $validArchiveFile = __DIR__ . '/../../aaa_update_test.tar.gz';
    $edit = [
      'files[project_upload]' => $validArchiveFile,
    ];
    $this->drupalGet('admin/modules/install');
    $this->submitForm($edit, 'Continue');
    $this->assertSession()->pageTextContains('AAA Update test is already present.');
    $this->assertSession()->addressEquals('admin/modules/install');

    // Ensure that a new module can be extracted and installed.
    $updaters = drupal_get_updaters();
    $moduleUpdater = $updaters['module']['class'];
    $installedInfoFilePath = $this->container->get('update.root') . '/' . $moduleUpdater::getRootDirectoryRelativePath() . '/update_test_new_module/update_test_new_module.info.yml';
    $this->assertFileDoesNotExist($installedInfoFilePath);
    $validArchiveFile = __DIR__ . '/../../update_test_new_module/8.x-1.0/update_test_new_module.tar.gz';
    $edit = [
      'files[project_upload]' => $validArchiveFile,
    ];
    $this->drupalGet('admin/modules/install');
    $this->submitForm($edit, 'Continue');
    // Check that submitting the form takes the user to authorize.php.
    $this->assertSession()->addressEquals('core/authorize.php');
    $this->assertSession()->titleEquals('Update manager | Drupal');
    // Check for a success message on the page, and check that the installed
    // module now exists in the expected place in the filesystem.
    $this->assertSession()->pageTextContains("Added / updated update_test_new_module successfully");
    $this->assertFileExists($installedInfoFilePath);
    // Ensure the links are relative to the site root and not
    // core/authorize.php.
    $this->assertSession()->linkExists('Add another module');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('update.module_install')->toString());
    $this->assertSession()->linkExists('Enable newly added modules');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('system.modules_list')->toString());
    $this->assertSession()->linkExists('Administration pages');
    $this->assertSession()->linkByHrefExists(Url::fromRoute('system.admin')->toString());
    // Ensure we can reach the "Add another module" link.
    $this->clickLink('Add another module');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/modules/install');

    // Check that the module has the correct version before trying to update
    // it. Since the module is installed in sites/simpletest, which only the
    // child site has access to, standard module API functions won't find it
    // when called here. To get the version, the info file must be parsed
    // directly instead.
    $info_parser = new InfoParserDynamic(DRUPAL_ROOT);
    $info = $info_parser->parse($installedInfoFilePath);
    $this->assertEquals('8.x-1.0', $info['version']);

    // Enable the module.
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[update_test_new_module][enable]' => TRUE], 'Install');

    // Define the update XML such that the new module downloaded above needs an
    // update from 8.x-1.0 to 8.x-1.1.
    $update_test_config = $this->config('update_test.settings');
    $system_info = [
      'update_test_new_module' => [
        'project' => 'update_test_new_module',
      ],
    ];
    $update_test_config->set('system_info', $system_info)->save();
    $xml_mapping = [
      'update_test_new_module' => '1_1',
    ];
    $this->refreshUpdateStatus($xml_mapping);

    // Run the updates for the new module.
    $this->drupalGet('admin/reports/updates/update');
    $this->submitForm(['projects[update_test_new_module]' => TRUE], 'Download these updates');
    $this->submitForm(['maintenance_mode' => FALSE], 'Continue');
    $this->assertSession()->pageTextContains('Update was completed successfully.');
    $this->assertSession()->pageTextContains("Added / updated update_test_new_module successfully");

    // Parse the info file again to check that the module has been updated to
    // 8.x-1.1.
    $info = $info_parser->parse($installedInfoFilePath);
    $this->assertEquals('8.x-1.1', $info['version']);
  }

  /**
   * Ensures that archiver extensions are properly merged in the UI.
   */
  public function testFileNameExtensionMerging() {
    $this->drupalGet('admin/modules/install');
    // Make sure the bogus extension supported by update_test.module is there.
    $this->assertSession()->responseMatches('/file extensions are supported:.*update-test-extension/');
    // Make sure it didn't clobber the first option from core.
    $this->assertSession()->responseMatches('/file extensions are supported:.*tar/');
  }

  /**
   * Checks the messages on update manager pages when missing a security update.
   */
  public function testUpdateManagerCoreSecurityUpdateMessages() {
    $setting = [
      '#all' => [
        'version' => '8.0.0',
      ],
    ];
    $this->config('update_test.settings')
      ->set('system_info', $setting)
      ->set('xml_map', ['drupal' => '0.2-sec'])
      ->save();
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    // Initialize the update status.
    $this->drupalGet('admin/reports/updates');

    // Now, make sure none of the Update manager pages have duplicate messages
    // about core missing a security update.

    $this->drupalGet('admin/modules/install');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/modules/update');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/appearance/install');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/appearance/update');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/reports/updates/install');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/reports/updates/update');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');

    $this->drupalGet('admin/update/ready');
    $this->assertSession()->pageTextNotContains('There is a security update available for your version of Drupal.');
  }

  /**
   * Tests only an *.info.yml file are detected without supporting files.
   */
  public function testUpdateDirectory() {
    $type = Updater::getUpdaterFromDirectory($this->root . '/core/modules/update/tests/modules/aaa_update_test');
    $this->assertEquals('Drupal\\Core\\Updater\\Module', $type, 'Detected a Module');

    $type = Updater::getUpdaterFromDirectory($this->root . '/core/modules/update/tests/themes/update_test_basetheme');
    $this->assertEquals('Drupal\\Core\\Updater\\Theme', $type, 'Detected a Theme.');
  }

}
