<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests the update script access and functionality.
 *
 * @group Update
 */
class UpdateScriptTest extends BrowserTestBase {

  use RequirementsPageTrait;

  protected const HANDBOOK_MESSAGE = 'Review the suggestions for resolving this incompatibility to repair your installation, and then re-run update.php.';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'update_script_test',
    'dblog',
    'language',
    'test_module_required_by_theme',
    'test_another_module_required_by_theme',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

  /**
   * The URL to the status report page.
   *
   * @var \Drupal\Core\Url
   */
  protected $statusReportUrl;

  /**
   * URL to the update.php script.
   *
   * @var string
   */
  private $updateUrl;

  /**
   * A user with the necessary permissions to administer software updates.
   *
   * @var \Drupal\user\UserInterface
   */
  private $updateUser;

  protected function setUp(): void {
    parent::setUp();
    $this->updateUrl = Url::fromRoute('system.db_update');
    $this->statusReportUrl = Url::fromRoute('system.status');
    $this->updateUser = $this->drupalCreateUser([
      'administer software updates',
      'access site in maintenance mode',
      'administer themes',
    ]);
  }

  /**
   * Tests access to the update script.
   */
  public function testUpdateAccess() {
    // Try accessing update.php without the proper permission.
    $regular_user = $this->drupalCreateUser();
    $this->drupalLogin($regular_user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(403);

    // Check that a link to the update page is not accessible to regular users.
    $this->drupalGet('/update-script-test/database-updates-menu-item');
    $this->assertSession()->linkNotExists('Run database updates');

    // Try accessing update.php as an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(403);

    // Check that a link to the update page is not accessible to anonymous
    // users.
    $this->drupalGet('/update-script-test/database-updates-menu-item');
    $this->assertSession()->linkNotExists('Run database updates');

    // Access the update page with the proper permission.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);

    // Check that a link to the update page is accessible to users with proper
    // permissions.
    $this->drupalGet('/update-script-test/database-updates-menu-item');
    $this->assertSession()->linkExists('Run database updates');

    // Access the update page as user 1.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);

    // Check that a link to the update page is accessible to user 1.
    $this->drupalGet('/update-script-test/database-updates-menu-item');
    $this->assertSession()->linkExists('Run database updates');
  }

  /**
   * Tests that requirements warnings and errors are correctly displayed.
   */
  public function testRequirements() {
    $update_script_test_config = $this->config('update_script_test.settings');
    $this->drupalLogin($this->updateUser);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->assertSession()->pageTextContains('No pending updates.');
    // Confirm that all caches were cleared.
    $this->assertSession()->pageTextContains('hook_cache_flush() invoked for update_script_test.module.');

    // If there is a requirements warning, we expect it to be initially
    // displayed, but clicking the link to proceed should allow us to go
    // through the rest of the update process uninterrupted.

    // First, run this test with pending updates to make sure they can be run
    // successfully.
    $this->drupalLogin($this->updateUser);
    $update_script_test_config->set('requirement_type', REQUIREMENT_WARNING)->save();
    drupal_set_installed_schema_version('update_script_test', drupal_get_installed_schema_version('update_script_test') - 1);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextContains('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertNoText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update_script_test_update_8001() update was executed successfully.');
    // Confirm that all caches were cleared.
    $this->assertSession()->pageTextContains('hook_cache_flush() invoked for update_script_test.module.');

    // Now try again without pending updates to make sure that works too.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextContains('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertNoText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink(t('Continue'));
    $this->assertSession()->pageTextContains('No pending updates.');
    // Confirm that all caches were cleared.
    $this->assertSession()->pageTextContains('hook_cache_flush() invoked for update_script_test.module.');

    // If there is a requirements error, it should be displayed even after
    // clicking the link to proceed (since the problem that triggered the error
    // has not been fixed).
    $update_script_test_config->set('requirement_type', REQUIREMENT_ERROR)->save();
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextContains('This is a requirements error provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertSession()->pageTextContains('This is a requirements error provided by the update_script_test module.');

    // Ensure that changes to a module's requirements that would cause errors
    // are displayed correctly.
    $update_script_test_config->set('requirement_type', REQUIREMENT_OK)->save();
    \Drupal::state()->set('update_script_test.system_info_alter', ['dependencies' => ['a_module_that_does_not_exist']]);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->responseContains('a_module_that_does_not_exist (Missing)');
    $this->assertSession()->responseContains('Update script test requires this module.');

    \Drupal::state()->set('update_script_test.system_info_alter', ['dependencies' => ['node (<7.x-0.0-dev)']]);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->assertEscaped('Node (Version <7.x-0.0-dev required)');
    $this->assertSession()->responseContains('Update script test requires this module and version. Currently using Node version ' . \Drupal::VERSION);

    // Test that issues with modules that themes depend on are properly
    // displayed.
    $this->assertSession()->responseNotContains('Test Module Required by Theme');
    $this->drupalGet('admin/appearance');
    $this->getSession()->getPage()->clickLink('Install Test Theme Depending on Modules theme');
    $this->assertSession()->addressEquals('admin/appearance');
    $this->assertSession()->pageTextContains('The Test Theme Depending on Modules theme has been installed');

    // Ensure that when a theme depends on a module and that module's
    // requirements change, errors are displayed in the same manner as modules
    // depending on other modules.
    \Drupal::state()->set('test_theme_depending_on_modules.system_info_alter', ['dependencies' => ['test_module_required_by_theme (<7.x-0.0-dev)']]);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->assertEscaped('Test Module Required by Theme (Version <7.x-0.0-dev required)');
    $this->assertSession()->responseContains('Test Theme Depending on Modules requires this module and version. Currently using Test Module Required by Theme version ' . \Drupal::VERSION);

    // Ensure that when a theme is updated to depend on an unavailable module,
    // errors are displayed in the same manner as modules depending on other
    // modules.
    \Drupal::state()->set('test_theme_depending_on_modules.system_info_alter', ['dependencies' => ['a_module_theme_needs_that_does_not_exist']]);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->responseContains('a_module_theme_needs_that_does_not_exist (Missing)');
    $this->assertSession()->responseContains('Test Theme Depending on Modules requires this module.');

  }

  /**
   * Tests that extension compatibility changes are handled correctly.
   *
   * @param array $correct_info
   *   The initial values for info.yml fail. These should compatible with core.
   * @param array $breaking_info
   *   The values to the info.yml that are not compatible with core.
   * @param string $expected_error
   *   The expected error.
   *
   * @dataProvider providerExtensionCompatibilityChange
   */
  public function testExtensionCompatibilityChange(array $correct_info, array $breaking_info, $expected_error) {
    $extension_type = $correct_info['type'];
    $this->drupalLogin(
      $this->drupalCreateUser(
        [
          'administer software updates',
          'administer site configuration',
          $extension_type === 'module' ? 'administer modules' : 'administer themes',
        ]
      )
    );

    $extension_machine_name = "changing_extension";
    $extension_name = "$extension_machine_name name";

    $test_error_text = "Incompatible $extension_type "
      . $expected_error
      . $extension_name
      . static::HANDBOOK_MESSAGE;
    $base_info = ['name' => $extension_name];
    if ($extension_type === 'theme') {
      $base_info['base theme'] = FALSE;
    }
    $folder_path = \Drupal::service('site.path') . "/{$extension_type}s/$extension_machine_name";
    $file_path = "$folder_path/$extension_machine_name.info.yml";
    mkdir($folder_path, 0777, TRUE);
    file_put_contents($file_path, Yaml::encode($base_info + $correct_info));
    $this->enableExtension($extension_type, $extension_machine_name, $extension_name);
    $this->assertInstalledExtensionConfig($extension_type, $extension_machine_name);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->assertUpdateWithNoError($test_error_text, $extension_type, $extension_machine_name);

    // Change the values in the info.yml and confirm updating is not possible.
    file_put_contents($file_path, Yaml::encode($base_info + $breaking_info));
    $this->assertErrorOnUpdate($test_error_text, $extension_type, $extension_machine_name);

    // Fix the values in the info.yml file and confirm updating is possible
    // again.
    file_put_contents($file_path, Yaml::encode($base_info + $correct_info));
    $this->assertUpdateWithNoError($test_error_text, $extension_type, $extension_machine_name);
  }

  /**
   * Date provider for testExtensionCompatibilityChange().
   */
  public function providerExtensionCompatibilityChange() {
    $incompatible_module_message = "The following module is installed, but it is incompatible with Drupal " . \Drupal::VERSION . ":";
    $incompatible_theme_message = "The following theme is installed, but it is incompatible with Drupal " . \Drupal::VERSION . ":";
    return [
      'module: core_version_requirement key incompatible' => [
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'module',
        ],
        [
          'core_version_requirement' => '8.7.7',
          'type' => 'module',
        ],
        $incompatible_module_message,
      ],
      'theme: core_version_requirement key incompatible' => [
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'theme',
        ],
        [
          'core_version_requirement' => '8.7.7',
          'type' => 'theme',
        ],
        $incompatible_theme_message,
      ],
      'module: php requirement' => [
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'module',
          'php' => 1,
        ],
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'module',
          'php' => 1000000000,
        ],
        'The following module is installed, but it is incompatible with PHP ' . phpversion() . ":",
      ],
      'theme: php requirement' => [
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'theme',
          'php' => 1,
        ],
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'theme',
          'php' => 1000000000,
        ],
        'The following theme is installed, but it is incompatible with PHP ' . phpversion() . ":",
      ],
      'module: core_version_requirement key missing' => [
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'module',
        ],
        [
          'core' => '8.x',
          'type' => 'module',
        ],
        $incompatible_module_message,
      ],
      'theme: core_version_requirement key missing' => [
        [
          'core_version_requirement' => '^8 || ^9',
          'type' => 'theme',
        ],
        [
          'core' => '8.x',
          'type' => 'theme',
        ],
        $incompatible_theme_message,
      ],
    ];
  }

  /**
   * Tests that a missing extension prevents updates.
   *
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   *
   * @dataProvider providerMissingExtension
   */
  public function testMissingExtension($extension_type) {
    $this->drupalLogin(
      $this->drupalCreateUser(
        [
          'administer software updates',
          'administer site configuration',
          $extension_type === 'module' ? 'administer modules' : 'administer themes',
        ]
      )
    );
    $extension_machine_name = "disappearing_$extension_type";
    $extension_name = 'The magically disappearing extension';
    $test_error_text = "Missing or invalid $extension_type "
      . "The following $extension_type is marked as installed in the core.extension configuration, but it is missing:"
      . $extension_machine_name
      . static::HANDBOOK_MESSAGE;
    $extension_info = [
      'name' => $extension_name,
      'type' => $extension_type,
      'core_version_requirement' => '^8 || ^9',
    ];
    if ($extension_type === 'theme') {
      $extension_info['base theme'] = FALSE;
    }
    $folder_path = \Drupal::service('site.path') . "/{$extension_type}s/$extension_machine_name";
    $file_path = "$folder_path/$extension_machine_name.info.yml";
    mkdir($folder_path, 0777, TRUE);
    file_put_contents($file_path, Yaml::encode($extension_info));
    $this->enableExtension($extension_type, $extension_machine_name, $extension_name);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->assertUpdateWithNoError($test_error_text, $extension_type, $extension_machine_name);

    // Delete the info.yml and confirm updates are prevented.
    unlink($file_path);
    $this->assertErrorOnUpdate($test_error_text, $extension_type, $extension_machine_name);

    // Add the info.yml file back and confirm we are able to go through the
    // update process uninterrupted.
    file_put_contents($file_path, Yaml::encode($extension_info));
    $this->assertUpdateWithNoError($test_error_text, $extension_type, $extension_machine_name);
  }

  /**
   * Tests that orphan schemas are handled properly.
   */
  public function testOrphanedSchemaEntries() {
    $this->drupalLogin($this->updateUser);

    // Insert a bogus value into the system.schema key/value storage for a
    // nonexistent module. This replicates what would happen if you had a module
    // installed and then completely remove it from the filesystem and clear it
    // out of the core.extension config list without uninstalling it cleanly.
    \Drupal::keyValue('system.schema')->set('my_already_removed_module', 8000);

    // Visit update.php and make sure we can click through to the 'No pending
    // updates' page without errors.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    // Make sure there are no pending updates (or uncaught exceptions).
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'No pending updates.');
    // Verify that we warn the admin about this situation.
    $this->assertSession()->elementTextEquals('xpath', '//div[@aria-label="Warning message"]', 'Warning message Module my_already_removed_module has an entry in the system.schema key/value storage, but is missing from your site. More information about this error.');

    // Try again with another orphaned entry, this time for a test module that
    // does exist in the filesystem.
    \Drupal::keyValue('system.schema')->delete('my_already_removed_module');
    \Drupal::keyValue('system.schema')->set('update_test_0', 8000);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    // There should not be any pending updates.
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'No pending updates.');
    // But verify that we warn the admin about this situation.
    $this->assertSession()->elementTextEquals('xpath', '//div[@aria-label="Warning message"]', 'Warning message Module update_test_0 has an entry in the system.schema key/value storage, but is not installed. More information about this error.');

    // Finally, try with both kinds of orphans and make sure we get both warnings.
    \Drupal::keyValue('system.schema')->set('my_already_removed_module', 8000);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    // There still should not be any pending updates.
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'No pending updates.');
    // Verify that we warn the admin about both orphaned entries.
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Warning message"]', 'Module update_test_0 has an entry in the system.schema key/value storage, but is not installed. More information about this error.');
    $this->assertSession()->elementTextNotContains('xpath', '//div[@aria-label="Warning message"]', 'Module update_test_0 has an entry in the system.schema key/value storage, but is missing from your site.');
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Warning message"]', 'Module my_already_removed_module has an entry in the system.schema key/value storage, but is missing from your site. More information about this error.');
    $this->assertSession()->elementTextNotContains('xpath', '//div[@aria-label="Warning message"]', 'Module my_already_removed_module has an entry in the system.schema key/value storage, but is not installed.');
  }

  /**
   * Data provider for testMissingExtension().
   */
  public function providerMissingExtension() {
    return [
      'module' => ['module'],
      'theme' => ['theme'],
    ];
  }

  /**
   * Enables an extension using the UI.
   *
   * @param string $extension_type
   *   The extension type.
   * @param string $extension_machine_name
   *   The extension machine name.
   * @param string $extension_name
   *   The extension name.
   */
  protected function enableExtension($extension_type, $extension_machine_name, $extension_name) {
    if ($extension_type === 'module') {
      $edit = [
        "modules[$extension_machine_name][enable]" => $extension_machine_name,
      ];
      $this->drupalGet('admin/modules');
      $this->submitForm($edit, 'Install');
    }
    elseif ($extension_type === 'theme') {
      $this->drupalGet('admin/appearance');
      $this->click("a[title~=\"$extension_name\"]");
    }
  }

  /**
   * Tests the effect of using the update script on the theme system.
   */
  public function testThemeSystem() {
    // Since visiting update.php triggers a rebuild of the theme system from an
    // unusual maintenance mode environment, we check that this rebuild did not
    // put any incorrect information about the themes into the database.
    $original_theme_data = $this->config('core.extension')->get('theme');
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $final_theme_data = $this->config('core.extension')->get('theme');
    $this->assertEquals($original_theme_data, $final_theme_data, 'Visiting update.php does not alter the information about themes stored in the database.');
  }

  /**
   * Tests update.php when there are no updates to apply.
   */
  public function testNoUpdateFunctionality() {
    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->assertSession()->pageTextContains('No pending updates.');
    $this->assertSession()->linkNotExists('Administration pages');
    $this->assertEmpty($this->xpath('//main//a[contains(@href, :href)]', [':href' => 'update.php']));
    $this->clickLink('Front page');
    $this->assertSession()->statusCodeEquals(200);

    // Click through update.php with 'access administration pages' permission.
    $admin_user = $this->drupalCreateUser([
      'administer software updates',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->assertSession()->pageTextContains('No pending updates.');
    $this->assertSession()->linkExists('Administration pages');
    $this->assertEmpty($this->xpath('//main//a[contains(@href, :href)]', [':href' => 'update.php']));
    $this->clickLink('Administration pages');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests update.php after performing a successful update.
   */
  public function testSuccessfulUpdateFunctionality() {
    $initial_maintenance_mode = $this->container->get('state')->get('system.maintenance_mode');
    $this->assertNull($initial_maintenance_mode, 'Site is not in maintenance mode.');
    $this->runUpdates($initial_maintenance_mode);
    $final_maintenance_mode = $this->container->get('state')->get('system.maintenance_mode');
    $this->assertEquals($initial_maintenance_mode, $final_maintenance_mode, 'Maintenance mode should not have changed after database updates.');

    // Reset the static cache to ensure we have the most current setting.
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEquals(8001, $schema_version, 'update_script_test schema version is 8001 after updating.');

    // Set the installed schema version to one less than the current update.
    drupal_set_installed_schema_version('update_script_test', $schema_version - 1);
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEquals(8000, $schema_version, 'update_script_test schema version overridden to 8000.');

    // Click through update.php with 'access administration pages' and
    // 'access site reports' permissions.
    $admin_user = $this->drupalCreateUser([
      'administer software updates',
      'access administration pages',
      'access site reports',
      'access site in maintenance mode',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Updates were attempted.');
    $this->assertSession()->linkExists('logged');
    $this->assertSession()->linkExists('Administration pages');
    $this->assertEmpty($this->xpath('//main//a[contains(@href, :href)]', [':href' => 'update.php']));
    $this->clickLink('Administration pages');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests update.php while in maintenance mode.
   */
  public function testMaintenanceModeUpdateFunctionality() {
    $this->container->get('state')
      ->set('system.maintenance_mode', TRUE);
    $initial_maintenance_mode = $this->container->get('state')
      ->get('system.maintenance_mode');
    $this->assertTrue($initial_maintenance_mode, 'Site is in maintenance mode.');
    $this->runUpdates($initial_maintenance_mode);
    $final_maintenance_mode = $this->container->get('state')
      ->get('system.maintenance_mode');
    $this->assertEquals($initial_maintenance_mode, $final_maintenance_mode, 'Maintenance mode should not have changed after database updates.');
  }

  /**
   * Tests performing updates with update.php in a multilingual environment.
   */
  public function testSuccessfulMultilingualUpdateFunctionality() {
    // Add some custom languages.
    foreach (['aa', 'bb'] as $language_code) {
      ConfigurableLanguage::create([
          'id' => $language_code,
          'label' => $this->randomMachineName(),
        ])->save();
    }

    $config = \Drupal::service('config.factory')->getEditable('language.negotiation');
    // Ensure path prefix is used to determine the language.
    $config->set('url.source', 'path_prefix');
    // Ensure that there's a path prefix set for english as well.
    $config->set('url.prefixes.en', 'en');
    $config->save();

    // Reset the static cache to ensure we have the most current setting.
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEquals(8001, $schema_version, 'update_script_test schema version is 8001 after updating.');

    // Set the installed schema version to one less than the current update.
    drupal_set_installed_schema_version('update_script_test', $schema_version - 1);
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEquals(8000, $schema_version, 'update_script_test schema version overridden to 8000.');

    // Create admin user.
    $admin_user = $this->drupalCreateUser([
      'administer software updates',
      'access administration pages',
      'access site reports',
      'access site in maintenance mode',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // Visit status report page and ensure, that link to update.php has no path prefix set.
    $this->drupalGet('en/admin/reports/status', ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/update.php');
    $this->assertSession()->linkByHrefNotExists('en/update.php');

    // Click through update.php with 'access administration pages' and
    // 'access site reports' permissions.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Updates were attempted.');
    $this->assertSession()->linkExists('logged');
    $this->assertSession()->linkExists('Administration pages');
    $this->assertEmpty($this->xpath('//main//a[contains(@href, :href)]', [':href' => 'update.php']));
    $this->clickLink('Administration pages');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests maintenance mode link on update.php.
   */
  public function testMaintenanceModeLink() {
    $full_admin_user = $this->drupalCreateUser([
      'administer software updates',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($full_admin_user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('maintenance mode');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', 'main h1', 'Maintenance mode');

    // Now login as a user with only 'administer software updates' (but not
    // 'administer site configuration') permission and try again.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('maintenance mode');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', 'main h1', 'Maintenance mode');
  }

  /**
   * Helper function to run updates via the browser.
   */
  protected function runUpdates($maintenance_mode) {
    $schema_version = drupal_get_installed_schema_version('update_script_test');
    $this->assertEquals(8001, $schema_version, 'update_script_test is initially installed with schema version 8001.');

    // Set the installed schema version to one less than the current update.
    drupal_set_installed_schema_version('update_script_test', $schema_version - 1);
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEquals(8000, $schema_version, 'update_script_test schema version overridden to 8000.');

    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->updateUser);
    if ($maintenance_mode) {
      $this->assertSession()->pageTextContains('Operating in maintenance mode.');
    }
    else {
      $this->assertNoText('Operating in maintenance mode.');
    }
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();

    // Verify that updates were completed successfully.
    $this->assertSession()->pageTextContains('Updates were attempted.');
    $this->assertSession()->linkExists('site');
    $this->assertSession()->pageTextContains('The update_script_test_update_8001() update was executed successfully.');

    // Verify that no 7.x updates were run.
    $this->assertNoText('The update_script_test_update_7200() update was executed successfully.');
    $this->assertNoText('The update_script_test_update_7201() update was executed successfully.');

    // Verify that there are no links to different parts of the workflow.
    $this->assertSession()->linkNotExists('Administration pages');
    $this->assertEmpty($this->xpath('//main//a[contains(@href, :href)]', [':href' => 'update.php']));
    $this->assertSession()->linkNotExists('logged');

    // Verify the front page can be visited following the upgrade.
    $this->clickLink('Front page');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Returns the Drupal 7 system table schema.
   */
  public function getSystemSchema() {
    return [
      'description' => "A list of all modules, themes, and theme engines that are or have been installed in Drupal's file system.",
      'fields' => [
        'filename' => [
          'description' => 'The path of the primary file for this item, relative to the Drupal root; e.g. modules/node/node.module.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'name' => [
          'description' => 'The name of the item; e.g. node.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'type' => [
          'description' => 'The type of the item, either module, theme, or theme_engine.',
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        ],
        'owner' => [
          'description' => "A theme's 'parent' . Can be either a theme or an engine.",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'status' => [
          'description' => 'Boolean indicating whether or not this item is enabled.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'bootstrap' => [
          'description' => "Boolean indicating whether this module is loaded during Drupal's early bootstrapping phase (e.g. even before the page cache is consulted).",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'schema_version' => [
          'description' => "The module's database schema version number. -1 if the module is not installed (its tables do not exist); \Drupal::CORE_MINIMUM_SCHEMA_VERSION or the largest N of the module's hook_update_N() function that has either been run or existed when the module was first installed.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => -1,
          'size' => 'small',
        ],
        'weight' => [
          'description' => "The order in which this module's hooks should be invoked relative to other modules. Equal-weighted modules are ordered by name.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'info' => [
          'description' => "A serialized array containing information from the module's .info file; keys can include name, description, package, version, core, dependencies, and php.",
          'type' => 'blob',
          'not null' => FALSE,
        ],
      ],
      'primary key' => ['filename'],
      'indexes' => [
        'system_list' => ['status', 'bootstrap', 'type', 'weight', 'name'],
        'type_name' => ['type', 'name'],
      ],
    ];
  }

  /**
   * Asserts that an installed extension's config setting is correct.
   *
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   * @param string $extension_machine_name
   *   The extension machine name.
   */
  protected function assertInstalledExtensionConfig($extension_type, $extension_machine_name) {
    $extension_config = $this->container->get('config.factory')->getEditable('core.extension');
    $this->assertSame(0, $extension_config->get("$extension_type.$extension_machine_name"));
  }

  /**
   * Asserts a particular error is not shown on update and status report pages.
   *
   * @param string $unexpected_error_text
   *   The error text that should not be shown.
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   * @param string $extension_machine_name
   *   The extension machine name.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertUpdateWithNoError($unexpected_error_text, $extension_type, $extension_machine_name) {
    $assert_session = $this->assertSession();
    $this->drupalGet($this->statusReportUrl);
    $this->assertSession()->pageTextNotContains($unexpected_error_text);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextNotContains($unexpected_error_text);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $assert_session->pageTextContains('No pending updates.');
    $this->assertInstalledExtensionConfig($extension_type, $extension_machine_name);
  }

  /**
   * Asserts an error is shown on the update and status report pages.
   *
   * @param string $expected_error_text
   *   The expected error text.
   * @param string $extension_type
   *   The extension type, either 'module' or 'theme'.
   * @param string $extension_machine_name
   *   The extension machine name.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertErrorOnUpdate($expected_error_text, $extension_type, $extension_machine_name) {
    $assert_session = $this->assertSession();
    $this->drupalGet($this->statusReportUrl);
    $this->assertSession()->pageTextContains($expected_error_text);

    // Reload the update page to ensure the extension with the breaking values
    // has not been uninstalled or otherwise affected.
    for ($reload = 0; $reload <= 1; $reload++) {
      $this->drupalGet($this->updateUrl, ['external' => TRUE]);
      $this->assertSession()->pageTextContains($expected_error_text);
      $assert_session->linkNotExists('Continue');
    }
    $this->assertInstalledExtensionConfig($extension_type, $extension_machine_name);
  }

}
