<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Update\UpdateScriptTest.
 */

namespace Drupal\system\Tests\Update;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the update system functionality.
 */
class UpdateScriptTest extends WebTestBase {
  private $update_url;
  private $update_user;

  public static function getInfo() {
    return array(
      'name' => 'Update functionality',
      'description' => 'Tests the update script access and functionality.',
      'group' => 'Update',
    );
  }

  function setUp() {
    parent::setUp(array('update_script_test', 'dblog'));
    $this->update_url = $GLOBALS['base_url'] . '/core/update.php';
    $this->update_user = $this->drupalCreateUser(array('administer software updates'));
  }

  /**
   * Tests access to the update script.
   */
  function testUpdateAccess() {
    // Try accessing update.php without the proper permission.
    $regular_user = $this->drupalCreateUser();
    $this->drupalLogin($regular_user);
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->assertResponse(403);

    // Try accessing update.php as an anonymous user.
    $this->drupalLogout();
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->assertResponse(403);

    // Access the update page with the proper permission.
    $this->drupalLogin($this->update_user);
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->assertResponse(200);

    // Access the update page as user 1.
    $user1 = user_load(1);
    $user1->pass_raw = user_password();
    require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'core/includes/password.inc');
    $user1->pass = user_hash_password(trim($user1->pass_raw));
    db_query("UPDATE {users} SET pass = :pass WHERE uid = :uid", array(':pass' => $user1->pass, ':uid' => $user1->uid));
    $this->drupalLogin($user1);
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->assertResponse(200);
  }

  /**
   * Tests that requirements warnings and errors are correctly displayed.
   */
  function testRequirements() {
    $this->drupalLogin($this->update_user);

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->drupalPost(NULL, array(), t('Continue'));
    $this->assertText(t('No pending updates.'), t('End of update process was reached.'));
    // Confirm that all caches were cleared.
    $this->assertText(t('hook_cache_flush() invoked for update_script_test.module.'), 'Caches were cleared when there were no requirements warnings or errors.');

    // If there is a requirements warning, we expect it to be initially
    // displayed, but clicking the link to proceed should allow us to go
    // through the rest of the update process uninterrupted.

    // First, run this test with pending updates to make sure they can be run
    // successfully.
    variable_set('update_script_test_requirement_type', REQUIREMENT_WARNING);
    drupal_set_installed_schema_version('update_script_test', drupal_get_installed_schema_version('update_script_test') - 1);
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->assertText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertNoText('This is a requirements warning provided by the update_script_test module.');
    $this->drupalPost(NULL, array(), t('Continue'));
    $this->drupalPost(NULL, array(), t('Apply pending updates'));
    $this->assertText(t('The update_script_test_update_8000() update was executed successfully.'), t('End of update process was reached.'));
    // Confirm that all caches were cleared.
    $this->assertText(t('hook_cache_flush() invoked for update_script_test.module.'), 'Caches were cleared after resolving a requirements warning and applying updates.');

    // Now try again without pending updates to make sure that works too.
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $this->assertText('This is a requirements warning provided by the update_script_test module.');
    $this->clickLink('try again');
    $this->assertNoText('This is a requirements warning provided by the update_script_test module.');
    $this->drupalPost(NULL, array(), t('Continue'));
    $this->assertText(t('No pending updates.'), t('End of update process was reached.'));
    // Confirm that all caches were cleared.
    $this->assertText(t('hook_cache_flush() invoked for update_script_test.module.'), 'Caches were cleared after applying updates and re-running the script.');

    // If there is a requirements error, it should be displayed even after
    // clicking the link to proceed (since the problem that triggered the error
    // has not been fixed).
    variable_set('update_script_test_requirement_type', REQUIREMENT_ERROR);
    $this->drupalGet($this->update_url, array('external' => TRUE));
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
    $original_theme_data = db_query("SELECT * FROM {system} WHERE type = 'theme' ORDER BY name")->fetchAll();
    $this->drupalLogin($this->update_user);
    $this->drupalGet($this->update_url, array('external' => TRUE));
    $final_theme_data = db_query("SELECT * FROM {system} WHERE type = 'theme' ORDER BY name")->fetchAll();
    $this->assertEqual($original_theme_data, $final_theme_data, t('Visiting update.php does not alter the information about themes stored in the database.'));
  }

  /**
   * Tests update.php when there are no updates to apply.
   */
  function testNoUpdateFunctionality() {
    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->update_user);
    $this->drupalPost($this->update_url, array(), t('Continue'), array('external' => TRUE));
    $this->assertText(t('No pending updates.'));
    $this->assertNoLink('Administration pages');
    $this->clickLink('Front page');
    $this->assertResponse(200);

    // Click through update.php with 'access administration pages' permission.
    $admin_user = $this->drupalCreateUser(array('administer software updates', 'access administration pages'));
    $this->drupalLogin($admin_user);
    $this->drupalPost($this->update_url, array(), t('Continue'), array('external' => TRUE));
    $this->assertText(t('No pending updates.'));
    $this->clickLink('Administration pages');
    $this->assertResponse(200);
  }

  /**
   * Tests update.php after performing a successful update.
   */
  function testSuccessfulUpdateFunctionality() {
    drupal_set_installed_schema_version('update_script_test', drupal_get_installed_schema_version('update_script_test') - 1);
    // Click through update.php with 'administer software updates' permission.
    $this->drupalLogin($this->update_user);
    $this->drupalPost($this->update_url, array(), t('Continue'), array('external' => TRUE));
    $this->drupalPost(NULL, array(), t('Apply pending updates'));
    $this->assertText('Updates were attempted.');
    $this->assertLink('site');
    $this->assertNoLink('Administration pages');
    $this->assertNoLink('logged');
    $this->clickLink('Front page');
    $this->assertResponse(200);

    drupal_set_installed_schema_version('update_script_test', drupal_get_installed_schema_version('update_script_test') - 1);
    // Click through update.php with 'access administration pages' and
    // 'access site reports' permissions.
    $admin_user = $this->drupalCreateUser(array('administer software updates', 'access administration pages', 'access site reports'));
    $this->drupalLogin($admin_user);
    $this->drupalPost($this->update_url, array(), t('Continue'), array('external' => TRUE));
    $this->drupalPost(NULL, array(), t('Apply pending updates'));
    $this->assertText('Updates were attempted.');
    $this->assertLink('logged');
    $this->clickLink('Administration pages');
    $this->assertResponse(200);
  }
}
