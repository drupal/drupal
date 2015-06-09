<?php

/**
 * @file
 * Definition of Drupal\help\Tests\HelpTest.
 */

namespace Drupal\help\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verify help display and user access to help based on permissions.
 *
 * @group help
 */
class HelpTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * The help_test module implements hook_help() but does not provide a module
   * overview page.
   *
   * @var array.
   */
  public static $modules = array('help_test');

  /**
   * Use the Standard profile to test help implementations of many core modules.
   */
  protected $profile = 'standard';

  /**
   * The admin user that will be created.
   */
  protected $adminUser;

  /**
   * The anonymous user that will be created.
   */
  protected $anyUser;

  protected function setUp() {
    parent::setUp();

    $this->getModuleList();

    // Create users.
    $this->adminUser = $this->drupalCreateUser(array('access administration pages', 'view the administration theme', 'administer permissions'));
    $this->anyUser = $this->drupalCreateUser(array());
  }

  /**
   * Logs in users, creates dblog events, and tests dblog functionality.
   */
  public function testHelp() {
    // Login the root user to ensure as many admin links appear as possible on
    // the module overview pages.
    $this->drupalLogin($this->rootUser);
    $this->verifyHelp();

    // Login the regular user.
    $this->drupalLogin($this->anyUser);
    $this->verifyHelp(403);

    // Verify that introductory help text exists, goes for 100% module coverage.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/help');
    $this->assertRaw(t('For more information, refer to the subjects listed in the Help Topics section or to the <a href="!docs">online documentation</a> and <a href="!support">support</a> pages at <a href="!drupal">drupal.org</a>.', array('!docs' => 'https://www.drupal.org/documentation', '!support' => 'https://www.drupal.org/support', '!drupal' => 'https://www.drupal.org')), 'Help intro text correctly appears.');

    // Verify that help topics text appears.
    $this->assertRaw('<h2>' . t('Help topics') . '</h2><p>' . t('Help is available on the following items:') . '</p>', 'Help topics text correctly appears.');

    // Make sure links are properly added for modules implementing hook_help().
    foreach ($this->getModuleList() as $module => $name) {
      $this->assertLink($name, 0, format_string('Link properly added to @name (admin/help/@module)', array('@module' => $module, '@name' => $name)));
    }

    // Ensure that module which does not provide an module overview page is
    // handled correctly.
    $this->clickLink(\Drupal::moduleHandler()->getName('help_test'));
    $this->assertRaw(t('No help is available for module %module.', array('%module' => \Drupal::moduleHandler()->getName('help_test'))));
  }

  /**
   * Verifies the logged in user has access to the various help nodes.
   *
   * @param integer $response
   *   An HTTP response code.
   */
  protected function verifyHelp($response = 200) {
    $this->drupalGet('admin/index');
    $this->assertResponse($response);
    if ($response == 200) {
      $this->assertText('This page shows you all available administration tasks for each module.');
    }
    else {
      $this->assertNoText('This page shows you all available administration tasks for each module.');
    }

    foreach ($this->getModuleList() as $module => $name) {
      // View module help node.
      $this->drupalGet('admin/help/' . $module);
      $this->assertResponse($response);
      if ($response == 200) {
        $this->assertTitle($name . ' | Drupal', format_string('%module title was displayed', array('%module' => $module)));
        $this->assertRaw('<h1 class="page-title">' . t($name) . '</h1>', format_string('%module heading was displayed', array('%module' => $module)));
        $admin_tasks = system_get_module_admin_tasks($module, system_get_info('module', $module));
        if (!empty($admin_tasks)) {
          $this->assertText(t('@module administration pages', array('@module' => $name)));
        }
        foreach ($admin_tasks as $task) {
          $this->assertLink($task['title']);
        }
      }
    }
  }

  /**
   * Gets the list of enabled modules that implement hook_help().
   *
   * @return array
   *   A list of enabled modules.
   */
  protected function getModuleList() {
    $modules = array();
    $module_data = system_rebuild_module_data();
    foreach (\Drupal::moduleHandler()->getImplementations('help') as $module) {
      $modules[$module] = $module_data[$module]->info['name'];
    }
    return $modules;
  }
}
