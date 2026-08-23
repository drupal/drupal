<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update script access and functionality.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class UpdateScriptTest extends UpdateScriptTestBase {

  /**
   * Tests access to the update script.
   */
  public function testUpdateAccess(): void {
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

    // Access the update page as administrator.
    $this->drupalLogin($this->createUser([
      'administer software updates',
      'access site in maintenance mode',
      'administer themes',
    ]));
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);

    // Check that a link to the update page is accessible to users with proper
    // permissions.
    $this->drupalGet('/update-script-test/database-updates-menu-item');
    $this->assertSession()->linkExists('Run database updates');
  }

  /**
   * Tests that requirements warnings and errors are correctly displayed.
   */
  public function testRequirements(): void {
    $update_script_test_config = $this->config('update_script_test.settings');
    $this->drupalLogin($this->updateUser);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('No pending updates.');
    // Confirm that all caches were cleared.
    $this->assertSession()->pageTextContains('hook_cache_flush() invoked for update_script_test.module.');

    // If there is a requirements warning, we expect it to be initially
    // displayed, but clicking the link to proceed should allow us to go
    // through the rest of the update process uninterrupted.

    // First, run this test with pending updates to make sure they can be run
    // successfully.
    $this->drupalLogin($this->updateUser);
    $update_script_test_config->set('requirement_type', RequirementSeverity::Warning->value)->save();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $update_registry->setInstalledVersion('update_script_test', $update_registry->getInstalledVersion('update_script_test') - 1);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextContains('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertSession()->pageTextNotContains('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('Continue');
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The update_script_test_update_8001() update was executed successfully.');
    // Confirm that all caches were cleared.
    $this->assertSession()->pageTextContains('hook_cache_flush() invoked for update_script_test.module.');

    // Now try again without pending updates to make sure that works too.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextContains('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertSession()->pageTextNotContains('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('No pending updates.');
    // Confirm that all caches were cleared.
    $this->assertSession()->pageTextContains('hook_cache_flush() invoked for update_script_test.module.');

    // If there is a requirements error, it should be displayed even after
    // clicking the link to proceed (since the problem that triggered the error
    // has not been fixed).
    $update_script_test_config->set('requirement_type', RequirementSeverity::Error->value)->save();
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->pageTextContains('This is a requirements error provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertSession()->pageTextContains('This is a requirements error provided by the update_script_test module.');

    // Ensure that changes to a module's requirements that would cause errors
    // are displayed correctly.
    $update_script_test_config->set('requirement_type', RequirementSeverity::OK->value)->save();
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
   */
  #[DataProvider('providerExtensionCompatibilityChange')]
  public function testExtensionCompatibilityChange(array $correct_info, array $breaking_info, string $expected_error): void {
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

    $extension_machine_names = ['changing_extension'];
    $extension_name = "$extension_machine_names[0] name";
    $test_error_urls = ['https://www.drupal.org/docs/updating-drupal/troubleshooting-database-updates'];

    $test_error_text = "Incompatible $extension_type "
      . $expected_error
      . $extension_name
      . static::HANDBOOK_MESSAGE;
    $base_info = ['name' => $extension_name];
    if ($extension_type === 'theme') {
      $base_info['base theme'] = FALSE;
    }
    $folder_path = \Drupal::getContainer()->getParameter('site.path') . "/{$extension_type}s/$extension_machine_names[0]";
    $file_path = "$folder_path/$extension_machine_names[0].info.yml";
    mkdir($folder_path, 0777, TRUE);
    $this->writeInfoFile($file_path, $base_info + $correct_info);
    $this->enableExtensions($extension_type, $extension_machine_names, [$extension_name]);
    $this->assertInstalledExtensionsConfig($extension_type, $extension_machine_names);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->drupalGet($this->statusReportUrl);
    $this->assertUpdateWithNoErrors([$test_error_text], $extension_type, $extension_machine_names);

    // Change the values in the info.yml and confirm updating is not possible.
    $this->writeInfoFile($file_path, $base_info + $breaking_info);
    $this->drupalGet($this->statusReportUrl);
    $this->assertErrorOnUpdates([$test_error_text], $extension_type, $extension_machine_names, $test_error_urls);

    // Fix the values in the info.yml file and confirm updating is possible
    // again.
    $this->writeInfoFile($file_path, $base_info + $correct_info);
    $this->drupalGet($this->statusReportUrl);
    $this->assertUpdateWithNoErrors([$test_error_text], $extension_type, $extension_machine_names);
  }

  /**
   * Data provider for testExtensionCompatibilityChange().
   */
  public static function providerExtensionCompatibilityChange(): array {
    $incompatible_module_message = "The following module is installed, but it is incompatible with Drupal " . \Drupal::VERSION . ":";
    $incompatible_theme_message = "The following theme is installed, but it is incompatible with Drupal " . \Drupal::VERSION . ":";
    return [
      'module: core_version_requirement key incompatible' => [
        [
          'core_version_requirement' => '>= 8',
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
          'core_version_requirement' => '>= 8',
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
          'core_version_requirement' => '>= 8',
          'type' => 'module',
          'php' => 1,
        ],
        [
          'core_version_requirement' => '>= 8',
          'type' => 'module',
          'php' => 1000000000,
        ],
        'The following module is installed, but it is incompatible with PHP ' . phpversion() . ":",
      ],
      'theme: php requirement' => [
        [
          'core_version_requirement' => '>= 8',
          'type' => 'theme',
          'php' => 1,
        ],
        [
          'core_version_requirement' => '>= 8',
          'type' => 'theme',
          'php' => 1000000000,
        ],
        'The following theme is installed, but it is incompatible with PHP ' . phpversion() . ":",
      ],
    ];
  }

  /**
   * Tests that orphan schemas are handled properly.
   */
  public function testOrphanedSchemaEntries(): void {
    $this->drupalLogin($this->updateUser);

    // Insert a bogus value into the system.schema key/value storage for a
    // nonexistent module. This replicates what would happen if you had a module
    // installed and then completely remove it from the filesystem and clear it
    // out of the core.extension config list without uninstalling it cleanly.
    \Drupal::service('update.update_hook_registry')->setInstalledVersion('my_already_removed_module', 8000);

    // Visit update.php and make sure we can click through to the 'No pending
    // updates' page without errors.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    // Make sure there are no pending updates (or uncaught exceptions).
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'No pending updates.');
    // Verify that we warn the admin about this situation.
    $this->assertSession()->elementTextEquals('xpath', '//div[@aria-label="Warning message"]', 'Warning message Module my_already_removed_module has an entry in the system.schema key/value storage, but is missing from your site. More information about this error.');

    // Try again with another orphaned entry, this time for a test module that
    // does exist in the filesystem.
    \Drupal::service('update.update_hook_registry')->deleteInstalledVersion('my_already_removed_module');
    \Drupal::service('update.update_hook_registry')->setInstalledVersion('update_test_0', 8000);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    // There should not be any pending updates.
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'No pending updates.');
    // But verify that we warn the admin about this situation.
    $this->assertSession()->elementTextEquals('xpath', '//div[@aria-label="Warning message"]', 'Warning message Module update_test_0 has an entry in the system.schema key/value storage, but is not installed. More information about this error.');

    // Finally, try with both kinds of orphans and make sure we get both
    // warnings.
    \Drupal::service('update.update_hook_registry')->setInstalledVersion('my_already_removed_module', 8000);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    // There still should not be any pending updates.
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Status message"]', 'No pending updates.');
    // Verify that we warn the admin about both orphaned entries.
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Warning message"]', 'Module update_test_0 has an entry in the system.schema key/value storage, but is not installed. More information about this error.');
    $this->assertSession()->elementTextNotContains('xpath', '//div[@aria-label="Warning message"]', 'Module update_test_0 has an entry in the system.schema key/value storage, but is missing from your site.');
    $this->assertSession()->elementTextContains('xpath', '//div[@aria-label="Warning message"]', 'Module my_already_removed_module has an entry in the system.schema key/value storage, but is missing from your site. More information about this error.');
    $this->assertSession()->elementTextNotContains('xpath', '//div[@aria-label="Warning message"]', 'Module my_already_removed_module has an entry in the system.schema key/value storage, but is not installed.');
  }

  /**
   * Tests the effect of using the update script on the theme system.
   */
  public function testThemeSystem(): void {
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
  public function testNoUpdateFunctionality(): void {
    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('No pending updates.');
    $this->assertSession()->linkNotExists('Administration pages');
    $this->assertSession()->elementNotExists('xpath', '//main//a[contains(@href, "update.php")]');
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
    $this->clickLink('Continue');
    $this->assertSession()->pageTextContains('No pending updates.');
    $this->assertSession()->linkExists('Administration pages');
    $this->assertSession()->elementNotExists('xpath', '//main//a[contains(@href, "update.php")]');
    $this->clickLink('Administration pages');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests update.php after performing a successful update.
   */
  public function testSuccessfulUpdateFunctionality(): void {
    $initial_maintenance_mode = $this->container->get('state')->get('system.maintenance_mode');
    $this->assertNull($initial_maintenance_mode, 'Site is not in maintenance mode.');
    $this->runUpdates($initial_maintenance_mode);
    $final_maintenance_mode = $this->container->get('state')->get('system.maintenance_mode');
    $this->assertEquals($initial_maintenance_mode, $final_maintenance_mode, 'Maintenance mode should not have changed after database updates.');

    // Reset the static cache to ensure we have the most current setting.
    $this->resetAll();
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $schema_version = $update_registry->getInstalledVersion('update_script_test');
    $this->assertEquals(8001, $schema_version, 'update_script_test schema version is 8001 after updating.');

    // Set the installed schema version to one less than the current update.
    $update_registry->setInstalledVersion('update_script_test', $schema_version - 1);
    $schema_version = $update_registry->getInstalledVersion('update_script_test');
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
    $this->clickLink('Continue');
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Updates were attempted.');
    $this->assertSession()->linkExists('logged');
    $this->assertSession()->linkExists('Administration pages');
    $this->assertSession()->elementNotExists('xpath', '//main//a[contains(@href, "update.php")]');
    $this->clickLink('Administration pages');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests update.php while in maintenance mode.
   */
  public function testMaintenanceModeUpdateFunctionality(): void {
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
  public function testSuccessfulMultilingualUpdateFunctionality(): void {
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
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $schema_version = $update_registry->getInstalledVersion('update_script_test');
    $this->assertEquals(8001, $schema_version, 'update_script_test schema version is 8001 after updating.');

    // Set the installed schema version to one less than the current update.
    $update_registry->setInstalledVersion('update_script_test', $schema_version - 1);
    $schema_version = $update_registry->getInstalledVersion('update_script_test');
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

    // Visit status report page and ensure, that link to update.php has no path
    // prefix set.
    $this->drupalGet('en/admin/reports/status', ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/update.php');
    $this->assertSession()->linkByHrefNotExists('en/update.php');

    // Click through update.php with 'access administration pages' and
    // 'access site reports' permissions.
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink('Continue');
    $this->clickLink('Apply pending updates');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('Updates were attempted.');
    $this->assertSession()->linkExists('logged');
    $this->assertSession()->linkExists('Administration pages');
    $this->assertSession()->linkExists('Status report');
    $this->assertSession()->elementNotExists('xpath', '//main//a[contains(@href, "update.php")]');
    $this->clickLink('Status report');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests maintenance mode link on update.php.
   */
  public function testMaintenanceModeLink(): void {
    $full_admin_user = $this->drupalCreateUser([
      'administer software updates',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($full_admin_user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
    $this->updateRequirementsProblem();
    $this->clickLink('maintenance mode');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', 'main h1', 'Maintenance mode');

    // Now login as a user with only 'administer software updates' (but not
    // 'administer site configuration') permission and try again.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->assertSession()->statusCodeEquals(200);
    $this->updateRequirementsProblem();
    $this->clickLink('maintenance mode');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', 'main h1', 'Maintenance mode');
  }

}
