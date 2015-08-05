<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Update\UpdateScriptTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the update script access and functionality.
 *
 * @group Update
 */
class UpdateScriptTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update_script_test', 'dblog');

  /**
   * {@inheritdoc}
   */
  protected $dumpHeaders = TRUE;

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

  protected function setUp() {
    parent::setUp();
    $this->updateUrl = Url::fromRoute('system.db_update');
    $this->updateUser = $this->drupalCreateUser(array('administer software updates', 'access site in maintenance mode'));
    \Drupal::service('entity.definition_update_manager')->applyUpdates();
  }

  /**
   * Tests access to the update script.
   */
  function testUpdateAccess() {
    // Try accessing update.php without the proper permission.
    $regular_user = $this->drupalCreateUser();
    $this->drupalLogin($regular_user);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertResponse(403);

    // Try accessing update.php as an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertResponse(403);

    // Access the update page with the proper permission.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertResponse(200);

    // Access the update page as user 1.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertResponse(200);
  }

  /**
   * Tests that requirements warnings and errors are correctly displayed.
   */
  function testRequirements() {
    $update_script_test_config = $this->config('update_script_test.settings');
    $this->drupalLogin($this->updateUser);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->clickLink(t('Continue'));
    $this->assertText(t('No pending updates.'), 'End of update process was reached.');
    // Confirm that all caches were cleared.
    $this->assertText(t('hook_cache_flush() invoked for update_script_test.module.'), 'Caches were cleared when there were no requirements warnings or errors.');

    // If there is a requirements warning, we expect it to be initially
    // displayed, but clicking the link to proceed should allow us to go
    // through the rest of the update process uninterrupted.

    // First, run this test with pending updates to make sure they can be run
    // successfully.
    $update_script_test_config->set('requirement_type', REQUIREMENT_WARNING)->save();
    drupal_set_installed_schema_version('update_script_test', drupal_get_installed_schema_version('update_script_test') - 1);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertNoText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));
    $this->assertText(t('The update_script_test_update_8001() update was executed successfully.'), 'End of update process was reached.');
    // Confirm that all caches were cleared.
    $this->assertText(t('hook_cache_flush() invoked for update_script_test.module.'), 'Caches were cleared after resolving a requirements warning and applying updates.');

    // Now try again without pending updates to make sure that works too.
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertNoText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink(t('Continue'));
    $this->assertText(t('No pending updates.'), 'End of update process was reached.');
    // Confirm that all caches were cleared.
    $this->assertText(t('hook_cache_flush() invoked for update_script_test.module.'), 'Caches were cleared after applying updates and re-running the script.');

    // If there is a requirements error, it should be displayed even after
    // clicking the link to proceed (since the problem that triggered the error
    // has not been fixed).
    $update_script_test_config->set('requirement_type', REQUIREMENT_ERROR)->save();
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->assertText('This is a requirements error provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertText('This is a requirements error provided by the update_script_test module.');
  }

  /**
   * Tests the effect of using the update script on the theme system.
   */
  function testThemeSystem() {
    // Since visiting update.php triggers a rebuild of the theme system from an
    // unusual maintenance mode environment, we check that this rebuild did not
    // put any incorrect information about the themes into the database.
    $original_theme_data = $this->config('core.extension')->get('theme');
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $final_theme_data = $this->config('core.extension')->get('theme');
    $this->assertEqual($original_theme_data, $final_theme_data, 'Visiting update.php does not alter the information about themes stored in the database.');
  }

  /**
   * Tests update.php when there are no updates to apply.
   */
  function testNoUpdateFunctionality() {
    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->updateUser);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->clickLink(t('Continue'));
    $this->assertText(t('No pending updates.'));
    $this->assertNoLink('Administration pages');
    $this->assertNoLinkByHref('update.php', 0);
    $this->clickLink('Front page');
    $this->assertResponse(200);

    // Click through update.php with 'access administration pages' permission.
    $admin_user = $this->drupalCreateUser(array('administer software updates', 'access administration pages'));
    $this->drupalLogin($admin_user);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->clickLink(t('Continue'));
    $this->assertText(t('No pending updates.'));
    $this->assertLink('Administration pages');
    $this->assertNoLinkByHref('update.php', 1);
    $this->clickLink('Administration pages');
    $this->assertResponse(200);
  }

  /**
   * Tests update.php after performing a successful update.
   */
  function testSuccessfulUpdateFunctionality() {
    $initial_maintenance_mode = $this->container->get('state')->get('system.maintenance_mode');
    $this->assertFalse($initial_maintenance_mode, 'Site is not in maintenance mode.');
    $this->updateScriptTest($initial_maintenance_mode);
    $final_maintenance_mode = $this->container->get('state')->get('system.maintenance_mode');
    $this->assertEqual($final_maintenance_mode, $initial_maintenance_mode, 'Maintenance mode should not have changed after database updates.');

    // Reset the static cache to ensure we have the most current setting.
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEqual($schema_version, 8001, 'update_script_test schema version is 8001 after updating.');

    // Set the installed schema version to one less than the current update.
    drupal_set_installed_schema_version('update_script_test', $schema_version - 1);
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEqual($schema_version, 8000, 'update_script_test schema version overridden to 8000.');

    // Click through update.php with 'access administration pages' and
    // 'access site reports' permissions.
    $admin_user = $this->drupalCreateUser(array('administer software updates', 'access administration pages', 'access site reports', 'access site in maintenance mode'));
    $this->drupalLogin($admin_user);
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));
    $this->assertText('Updates were attempted.');
    $this->assertLink('logged');
    $this->assertLink('Administration pages');
    $this->assertNoLinkByHref('update.php', 1);
    $this->clickLink('Administration pages');
    $this->assertResponse(200);
  }

  /**
   * Tests update.php while in maintenance mode.
   */
  function testMaintenanceModeUpdateFunctionality() {
    $this->container->get('state')
      ->set('system.maintenance_mode', TRUE);
    $initial_maintenance_mode = $this->container->get('state')
      ->get('system.maintenance_mode');
    $this->assertTrue($initial_maintenance_mode, 'Site is in maintenance mode.');
    $this->updateScriptTest($initial_maintenance_mode);
    $final_maintenance_mode = $this->container->get('state')
      ->get('system.maintenance_mode');
    $this->assertEqual($final_maintenance_mode, $initial_maintenance_mode, 'Maintenance mode should not have changed after database updates.');
  }

  /**
   * Helper function to run updates via the browser.
   */
  protected function updateScriptTest($maintenance_mode) {
    $schema_version = drupal_get_installed_schema_version('update_script_test');
    $this->assertEqual($schema_version, 8001, 'update_script_test is initially installed with schema version 8001.');

    // Set the installed schema version to one less than the current update.
    drupal_set_installed_schema_version('update_script_test', $schema_version - 1);
    $schema_version = drupal_get_installed_schema_version('update_script_test', TRUE);
    $this->assertEqual($schema_version, 8000, 'update_script_test schema version overridden to 8000.');

    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->updateUser);
    if ($maintenance_mode) {
      $this->assertText('Operating in maintenance mode.');
    }
    else {
      $this->assertNoText('Operating in maintenance mode.');
    }
    $this->drupalGet($this->updateUrl, array('external' => TRUE));
    $this->clickLink(t('Continue'));
    $this->clickLink(t('Apply pending updates'));

    // Verify that updates were completed successfully.
    $this->assertText('Updates were attempted.');
    $this->assertLink('site');
    $this->assertText('The update_script_test_update_8001() update was executed successfully.');

    // Verify that no 7.x updates were run.
    $this->assertNoText('The update_script_test_update_7200() update was executed successfully.');
    $this->assertNoText('The update_script_test_update_7201() update was executed successfully.');

    // Verify that there are no links to different parts of the workflow.
    $this->assertNoLink('Administration pages');
    $this->assertNoLinkByHref('update.php', 0);
    $this->assertNoLink('logged');

    // Verify the front page can be visited following the upgrade.
    $this->clickLink('Front page');
    $this->assertResponse(200);
  }

  /**
   * Returns the Drupal 7 system table schema.
   */
  public function getSystemSchema() {
    return array(
      'description' => "A list of all modules, themes, and theme engines that are or have been installed in Drupal's file system.",
      'fields' => array(
        'filename' => array(
          'description' => 'The path of the primary file for this item, relative to the Drupal root; e.g. modules/node/node.module.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'name' => array(
          'description' => 'The name of the item; e.g. node.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'type' => array(
          'description' => 'The type of the item, either module, theme, or theme_engine.',
          'type' => 'varchar',
          'length' => 12,
          'not null' => TRUE,
          'default' => '',
        ),
        'owner' => array(
          'description' => "A theme's 'parent' . Can be either a theme or an engine.",
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'status' => array(
          'description' => 'Boolean indicating whether or not this item is enabled.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'bootstrap' => array(
          'description' => "Boolean indicating whether this module is loaded during Drupal's early bootstrapping phase (e.g. even before the page cache is consulted).",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'schema_version' => array(
          'description' => "The module's database schema version number. -1 if the module is not installed (its tables do not exist); \Drupal::CORE_MINIMUM_SCHEMA_VERSION or the largest N of the module's hook_update_N() function that has either been run or existed when the module was first installed.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => -1,
          'size' => 'small',
        ),
        'weight' => array(
          'description' => "The order in which this module's hooks should be invoked relative to other modules. Equal-weighted modules are ordered by name.",
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'info' => array(
          'description' => "A serialized array containing information from the module's .info file; keys can include name, description, package, version, core, dependencies, and php.",
          'type' => 'blob',
          'not null' => FALSE,
        ),
      ),
      'primary key' => array('filename'),
      'indexes' => array(
        'system_list' => array('status', 'bootstrap', 'type', 'weight', 'name'),
        'type_name' => array('type', 'name'),
      ),
    );
  }
}
