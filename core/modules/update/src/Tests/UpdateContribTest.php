<?php

/**
 * @file
 * Definition of Drupal\update\Tests\UpdateContribTest.
 */

namespace Drupal\update\Tests;

use Drupal\Core\Utility\ProjectInfo;

/**
 * Tests how the Update Manager module handles contributed modules and themes in
 * a series of functional tests using mock XML data.
 *
 * @group update
 */
class UpdateContribTest extends UpdateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update_test', 'update', 'aaa_update_test', 'bbb_update_test', 'ccc_update_test');

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests when there is no available release data for a contrib module.
   */
  function testNoReleasesAvailable() {
    $system_info = array(
      '#all' => array(
        'version' => '7.0',
      ),
      'aaa_update_test' => array(
        'project' => 'aaa_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $this->refreshUpdateStatus(array('drupal' => '0', 'aaa_update_test' => 'no-releases'));
    $this->drupalGet('admin/reports/updates');
    // Cannot use $this->standardTests() because we need to check for the
    // 'No available releases found' string.
    $this->assertRaw('<h3>' . t('Drupal core') . '</h3>');
    $this->assertRaw(l(t('Drupal'), 'http://example.com/project/drupal'));
    $this->assertText(t('Up to date'));
    $this->assertRaw('<h3>' . t('Modules') . '</h3>');
    $this->assertNoText(t('Update available'));
    $this->assertText(t('No available releases found'));
    $this->assertNoRaw(l(t('AAA Update test'), 'http://example.com/project/aaa_update_test'));
  }

  /**
   * Tests the basic functionality of a contrib module on the status report.
   */
  function testUpdateContribBasic() {
    $system_info = array(
      '#all' => array(
        'version' => '7.0',
      ),
      'aaa_update_test' => array(
        'project' => 'aaa_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $this->refreshUpdateStatus(
      array(
        'drupal' => '0',
        'aaa_update_test' => '1_0',
      )
    );
    $this->standardTests();
    $this->assertText(t('Up to date'));
    $this->assertRaw('<h3>' . t('Modules') . '</h3>');
    $this->assertNoText(t('Update available'));
    $this->assertRaw(l(t('AAA Update test'), 'http://example.com/project/aaa_update_test'), 'Link to aaa_update_test project appears.');
  }

  /**
   * Tests that contrib projects are ordered by project name.
   *
   * If a project contains multiple modules, we want to make sure that the
   * available updates report is sorted by the parent project names, not by the
   * names of the modules included in each project. In this test case, we have
   * two contrib projects, "BBB Update test" and "CCC Update test". However, we
   * have a module called "aaa_update_test" that's part of the "CCC Update test"
   * project. We need to make sure that we see the "BBB" project before the
   * "CCC" project, even though "CCC" includes a module that's processed first
   * if you sort alphabetically by module name (which is the order we see things
   * inside system_rebuild_module_data() for example).
   */
  function testUpdateContribOrder() {
    // We want core to be version 7.0.
    $system_info = array(
      '#all' => array(
        'version' => '7.0',
      ),
      // All the rest should be visible as contrib modules at version 8.x-1.0.

      // aaa_update_test needs to be part of the "CCC Update test" project,
      // which would throw off the report if we weren't properly sorting by
      // the project names.
      'aaa_update_test' => array(
        'project' => 'ccc_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),

      // This should be its own project, and listed first on the report.
      'bbb_update_test' => array(
        'project' => 'bbb_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),

      // This will contain both aaa_update_test and ccc_update_test, and
      // should come after the bbb_update_test project.
      'ccc_update_test' => array(
        'project' => 'ccc_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $this->refreshUpdateStatus(array('drupal' => '0', '#all' => '1_0'));
    $this->standardTests();
    // We're expecting the report to say all projects are up to date.
    $this->assertText(t('Up to date'));
    $this->assertNoText(t('Update available'));
    // We want to see all 3 module names listed, since they'll show up either
    // as project names or as modules under the "Includes" listing.
    $this->assertText(t('AAA Update test'));
    $this->assertText(t('BBB Update test'));
    $this->assertText(t('CCC Update test'));
    // We want aaa_update_test included in the ccc_update_test project, not as
    // its own project on the report.
    $this->assertNoRaw(l(t('AAA Update test'), 'http://example.com/project/aaa_update_test'), 'Link to aaa_update_test project does not appear.');
    // The other two should be listed as projects.
    $this->assertRaw(l(t('BBB Update test'), 'http://example.com/project/bbb_update_test'), 'Link to bbb_update_test project appears.');
    $this->assertRaw(l(t('CCC Update test'), 'http://example.com/project/ccc_update_test'), 'Link to bbb_update_test project appears.');

    // We want to make sure we see the BBB project before the CCC project.
    // Instead of just searching for 'BBB Update test' or something, we want
    // to use the full markup that starts the project entry itself, so that
    // we're really testing that the project listings are in the right order.
    $bbb_project_link = '<div class="project"><a href="http://example.com/project/bbb_update_test">BBB Update test</a>';
    $ccc_project_link = '<div class="project"><a href="http://example.com/project/ccc_update_test">CCC Update test</a>';
    $this->assertTrue(strpos($this->drupalGetContent(), $bbb_project_link) < strpos($this->drupalGetContent(), $ccc_project_link), "'BBB Update test' project is listed before the 'CCC Update test' project");
  }

  /**
   * Tests that subthemes are notified about security updates for base themes.
   */
  function testUpdateBaseThemeSecurityUpdate() {
    // @todo https://www.drupal.org/node/2338175 base themes have to be
    //  installed.
    // Only install the subtheme, not the base theme.
    \Drupal::service('theme_handler')->install(array('update_test_subtheme'));

    // Define the initial state for core and the subtheme.
    $system_info = array(
      // We want core to be version 7.0.
      '#all' => array(
        'version' => '7.0',
      ),
      // Show the update_test_basetheme
      'update_test_basetheme' => array(
        'project' => 'update_test_basetheme',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
      // Show the update_test_subtheme
      'update_test_subtheme' => array(
        'project' => 'update_test_subtheme',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $xml_mapping = array(
      'drupal' => '0',
      'update_test_subtheme' => '1_0',
      'update_test_basetheme' => '1_1-sec',
    );
    $this->refreshUpdateStatus($xml_mapping);
    $this->assertText(t('Security update required!'));
    $this->assertRaw(l(t('Update test base theme'), 'http://example.com/project/update_test_basetheme'), 'Link to the Update test base theme project appears.');
  }

  /**
   * Tests that disabled themes are only shown when desired.
   *
   * @todo https://www.drupal.org/node/2338175 extensions can not be hidden and
   *   base themes have to be installed.
   */
  function testUpdateShowDisabledThemes() {
    $update_settings = \Drupal::config('update.settings');
    // Make sure all the update_test_* themes are disabled.
    $extension_config = \Drupal::config('core.extension');
    foreach ($extension_config->get('theme') as $theme => $weight) {
      if (preg_match('/^update_test_/', $theme)) {
        $extension_config->clear("theme.$theme");
      }
    }
    $extension_config->save();

    // Define the initial state for core and the test contrib themes.
    $system_info = array(
      // We want core to be version 7.0.
      '#all' => array(
        'version' => '7.0',
      ),
      // The update_test_basetheme should be visible and up to date.
      'update_test_basetheme' => array(
        'project' => 'update_test_basetheme',
        'version' => '8.x-1.1',
        'hidden' => FALSE,
      ),
      // The update_test_subtheme should be visible and up to date.
      'update_test_subtheme' => array(
        'project' => 'update_test_subtheme',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    // When there are contributed modules in the site's file system, the
    // total number of attempts made in the test may exceed the default value
    // of update_max_fetch_attempts. Therefore this variable is set very high
    // to avoid test failures in those cases.
    $update_settings->set('fetch.max_attempts', 99999)->save();
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $xml_mapping = array(
      'drupal' => '0',
      'update_test_subtheme' => '1_0',
      'update_test_basetheme' => '1_1-sec',
    );
    $base_theme_project_link = l(t('Update test base theme'), 'http://example.com/project/update_test_basetheme');
    $sub_theme_project_link = l(t('Update test subtheme'), 'http://example.com/project/update_test_subtheme');
    foreach (array(TRUE, FALSE) as $check_disabled) {
      $update_settings->set('check.disabled_extensions', $check_disabled)->save();
      $this->refreshUpdateStatus($xml_mapping);
      // In neither case should we see the "Themes" heading for installed
      // themes.
      $this->assertNoText(t('Themes'));
      if ($check_disabled) {
        $this->assertText(t('Disabled themes'));
        $this->assertRaw($base_theme_project_link, 'Link to the Update test base theme project appears.');
        $this->assertRaw($sub_theme_project_link, 'Link to the Update test subtheme project appears.');
      }
      else {
        $this->assertNoText(t('Disabled themes'));
        $this->assertNoRaw($base_theme_project_link, 'Link to the Update test base theme project does not appear.');
        $this->assertNoRaw($sub_theme_project_link, 'Link to the Update test subtheme project does not appear.');
      }
    }
  }

  /**
   * Tests updates with a hidden base theme.
   */
  function testUpdateHiddenBaseTheme() {
    module_load_include('compare.inc', 'update');

    // Install the subtheme.
    \Drupal::service('theme_handler')->install(array('update_test_subtheme'));

    // Add a project and initial state for base theme and subtheme.
    $system_info = array(
      // Hide the update_test_basetheme.
      'update_test_basetheme' => array(
        'project' => 'update_test_basetheme',
        'hidden' => TRUE,
      ),
      // Show the update_test_subtheme.
      'update_test_subtheme' => array(
        'project' => 'update_test_subtheme',
        'hidden' => FALSE,
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();
    $projects = update_get_projects();
    $theme_data = system_rebuild_theme_data();
    $project_info = new ProjectInfo();
    $project_info->processInfoList($projects, $theme_data, 'theme', TRUE);

    $this->assertTrue(!empty($projects['update_test_basetheme']), 'Valid base theme (update_test_basetheme) was found.');
  }

  /**
   * Makes sure that if we fetch from a broken URL, sane things happen.
   */
  function testUpdateBrokenFetchURL() {
    $system_info = array(
      '#all' => array(
        'version' => '7.0',
      ),
      'aaa_update_test' => array(
        'project' => 'aaa_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
      'bbb_update_test' => array(
        'project' => 'bbb_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
      'ccc_update_test' => array(
        'project' => 'ccc_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    \Drupal::config('update_test.settings')->set('system_info', $system_info)->save();

    $xml_mapping = array(
      'drupal' => '0',
      'aaa_update_test' => '1_0',
      'bbb_update_test' => 'does-not-exist',
      'ccc_update_test' => '1_0',
    );
    $this->refreshUpdateStatus($xml_mapping);

    $this->assertText(t('Up to date'));
    // We're expecting the report to say most projects are up to date, so we
    // hope that 'Up to date' is not unique.
    $this->assertNoUniqueText(t('Up to date'));
    // It should say we failed to get data, not that we're missing an update.
    $this->assertNoText(t('Update available'));

    // We need to check that this string is found as part of a project row,
    // not just in the "Failed to get available update data for ..." message
    // at the top of the page.
    $this->assertRaw('<div class="version-status">' . t('Failed to get available update data'));

    // We should see the output messages from fetching manually.
    $this->assertUniqueText(t('Checked available update data for 3 projects.'));
    $this->assertUniqueText(t('Failed to get available update data for one project.'));

    // The other two should be listed as projects.
    $this->assertRaw(l(t('AAA Update test'), 'http://example.com/project/aaa_update_test'), 'Link to aaa_update_test project appears.');
    $this->assertNoRaw(l(t('BBB Update test'), 'http://example.com/project/bbb_update_test'), 'Link to bbb_update_test project does not appear.');
    $this->assertRaw(l(t('CCC Update test'), 'http://example.com/project/ccc_update_test'), 'Link to bbb_update_test project appears.');
  }

  /**
   * Checks that hook_update_status_alter() works to change a status.
   *
   * We provide the same external data as if aaa_update_test 8.x-1.0 were
   * installed and that was the latest release. Then we use
   * hook_update_status_alter() to try to mark this as missing a security
   * update, then assert if we see the appropriate warnings on the right pages.
   */
  function testHookUpdateStatusAlter() {
    $update_test_config = \Drupal::config('update_test.settings');
    $update_admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer software updates'));
    $this->drupalLogin($update_admin_user);

    $system_info = array(
      '#all' => array(
        'version' => '7.0',
      ),
      'aaa_update_test' => array(
        'project' => 'aaa_update_test',
        'version' => '8.x-1.0',
        'hidden' => FALSE,
      ),
    );
    $update_test_config->set('system_info', $system_info)->save();
    $update_status = array(
      'aaa_update_test' => array(
        'status' => UPDATE_NOT_SECURE,
      ),
    );
    $update_test_config->set('update_status', $update_status)->save();
    $this->refreshUpdateStatus(
      array(
        'drupal' => '0',
        'aaa_update_test' => '1_0',
      )
    );
    $this->drupalGet('admin/reports/updates');
    $this->assertRaw('<h3>' . t('Modules') . '</h3>');
    $this->assertText(t('Security update required!'));
    $this->assertRaw(l(t('AAA Update test'), 'http://example.com/project/aaa_update_test'), 'Link to aaa_update_test project appears.');

    // Visit the reports page again without the altering and make sure the
    // status is back to normal.
    $update_test_config->set('update_status', array())->save();
    $this->drupalGet('admin/reports/updates');
    $this->assertRaw('<h3>' . t('Modules') . '</h3>');
    $this->assertNoText(t('Security update required!'));
    $this->assertRaw(l(t('AAA Update test'), 'http://example.com/project/aaa_update_test'), 'Link to aaa_update_test project appears.');

    // Turn the altering back on and visit the Update manager UI.
    $update_test_config->set('update_status', $update_status)->save();
    $this->drupalGet('admin/modules/update');
    $this->assertText(t('Security update'));

    // Turn the altering back off and visit the Update manager UI.
    $update_test_config->set('update_status', array())->save();
    $this->drupalGet('admin/modules/update');
    $this->assertNoText(t('Security update'));
  }

}
