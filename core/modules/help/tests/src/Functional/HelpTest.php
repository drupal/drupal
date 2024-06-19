<?php

declare(strict_types=1);

namespace Drupal\Tests\help\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify help display and user access to help based on permissions.
 *
 * @group help
 */
class HelpTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * The help_test module implements hook_help() but does not provide a module
   * overview page. The help_page_test module has a page section plugin that
   * returns no links.
   *
   * @var array
   */
  protected static $modules = [
    'block_content',
    'breakpoint',
    'editor',
    'help',
    'help_page_test',
    'help_test',
    'history',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * The admin user that will be created.
   */
  protected $adminUser;

  /**
   * The anonymous user that will be created.
   */
  protected $anyUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'access help pages',
      'view the administration theme',
      'administer permissions',
    ]);
    $this->anyUser = $this->drupalCreateUser([]);
  }

  /**
   * Logs in users, tests help pages.
   */
  public function testHelp(): void {
    // Log in the root user to ensure as many admin links appear as possible on
    // the module overview pages.
    $this->drupalLogin($this->drupalCreateUser([
      'access help pages',
      'access administration pages',
    ]));
    $this->verifyHelp();

    // Log in the regular user.
    $this->drupalLogin($this->anyUser);
    $this->verifyHelp(403);

    // Verify that introductory help text exists, goes for 100% module coverage.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/help');
    $this->assertSession()->responseContains('For more information, refer to the help listed on this page or to the <a href="https://www.drupal.org/documentation">online documentation</a> and <a href="https://www.drupal.org/support">support</a> pages at <a href="https://www.drupal.org">drupal.org</a>.');

    // Verify that hook_help() section title and description appear.
    $this->assertSession()->responseContains('<h2>Module overviews</h2>');
    $this->assertSession()->responseContains('<p>Module overviews are provided by modules. Overviews available for your installed modules:</p>');

    // Verify that an empty section is handled correctly.
    $this->assertSession()->responseContains('<h2>Empty section</h2>');
    $this->assertSession()->responseContains('<p>This description should appear.</p>');
    $this->assertSession()->pageTextContains('There is currently nothing in this section.');

    // Make sure links are properly added for modules implementing hook_help().
    foreach ($this->getModuleList() as $module => $name) {
      $this->assertSession()->linkExists($name, 0, new FormattableMarkup('Link properly added to @name (admin/help/@module)', ['@module' => $module, '@name' => $name]));
    }

    // Ensure a module which does not provide a module overview page is handled
    // correctly.
    $module_name = \Drupal::service('extension.list.module')->getName('help_test');
    $this->clickLink($module_name);
    $this->assertSession()->pageTextContains('No help is available for module ' . $module_name);

    // Verify that the order of topics is alphabetical by displayed module
    // name, by checking the order of some modules, including some that would
    // have a different order if it was done by machine name instead.
    $this->drupalGet('admin/help');
    $page_text = $this->getTextContent();
    $start = strpos($page_text, 'Module overviews');
    $pos = $start;
    $list = ['Block', 'Block Content', 'Breakpoint', 'History', 'Text Editor'];
    foreach ($list as $name) {
      $this->assertSession()->linkExists($name);
      $new_pos = strpos($page_text, $name, $start);
      $this->assertGreaterThan($pos, $new_pos, "Order of $name is not correct on page");
      $pos = $new_pos;
    }
  }

  /**
   * Verifies the logged in user has access to the various help pages.
   *
   * @param int $response
   *   (optional) An HTTP response code. Defaults to 200.
   */
  protected function verifyHelp($response = 200) {
    $this->drupalGet('admin/index');
    $this->assertSession()->statusCodeEquals($response);
    if ($response == 200) {
      $this->assertSession()->pageTextContains('This page shows you all available administration tasks for each module.');
    }
    else {
      $this->assertSession()->pageTextNotContains('This page shows you all available administration tasks for each module.');
    }

    $module_list = \Drupal::service('extension.list.module');
    foreach ($this->getModuleList() as $module => $name) {
      // View module help page.
      $this->drupalGet('admin/help/' . $module);
      $this->assertSession()->statusCodeEquals($response);
      if ($response == 200) {
        $this->assertSession()->titleEquals("$name | Drupal");
        $this->assertEquals($name, $this->cssSelect('h1.page-title')[0]->getText(), "$module heading was displayed");
        $info = $module_list->getExtensionInfo($module);
        $admin_tasks = \Drupal::service('system.module_admin_links_helper')->getModuleAdminLinks($module);
        if ($module_permissions_link = \Drupal::service('user.module_permissions_link_helper')->getModulePermissionsLink($module, $info['name'])) {
          $admin_tasks["user.admin_permissions.{$module}"] = $module_permissions_link;
        }
        if (!empty($admin_tasks)) {
          $this->assertSession()->pageTextContains($name . ' administration pages');
        }
        foreach ($admin_tasks as $task) {
          $this->assertSession()->linkExists($task['title']);
          // Ensure there are no double escaped '&' or '<' characters.
          $this->assertSession()->assertNoEscaped('&amp;');
          $this->assertSession()->assertNoEscaped('&lt;');
          // Ensure there are no escaped '<' characters.
          $this->assertSession()->assertNoEscaped('<');
        }
        // Ensure there are no double escaped '&' or '<' characters.
        $this->assertSession()->assertNoEscaped('&amp;');
        $this->assertSession()->assertNoEscaped('&lt;');

        // The help for CKEditor 5 intentionally has escaped '<' so leave this
        // iteration before the assertion below.
        if ($module === 'ckeditor5') {
          continue;
        }
        // Ensure there are no escaped '<' characters.
        $this->assertSession()->assertNoEscaped('<');
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
    $modules = [];
    $module_data = $this->container->get('extension.list.module')->getList();
    \Drupal::moduleHandler()->invokeAllWith(
      'help',
      function (callable $hook, string $module) use (&$modules, $module_data) {
        $modules[$module] = $module_data[$module]->info['name'];
      }
    );
    return $modules;
  }

}
