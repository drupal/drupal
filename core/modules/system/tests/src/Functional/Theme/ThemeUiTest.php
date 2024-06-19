<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use Drupal\TestTools\Extension\InfoWriterTrait;

/**
 * Tests the theme UI.
 *
 * @group Theme
 * @group #slow
 */
class ThemeUiTest extends BrowserTestBase {
  use InfoWriterTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules used for testing.
   *
   * @var array
   */
  protected $testModules = [
    'help' => 'Help',
    'test_module_required_by_theme' => 'Test Module Required by Theme',
    'test_another_module_required_by_theme' => 'Test Another Module Required by Theme',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer themes',
      'administer modules',
    ]));
  }

  /**
   * Tests permissions for enabling themes depending on disabled modules.
   */
  public function testModulePermissions(): void {
    // Log in as a user without permission to enable modules.
    $this->drupalLogin($this->drupalCreateUser([
      'administer themes',
    ]));
    $this->drupalGet('admin/appearance');

    // The links to install a theme that would enable modules should be replaced
    // by this message.
    $this->assertSession()->pageTextContains('This theme requires the listed modules to operate correctly. They must first be installed by a user with permissions to do so.');

    // The install page should not be reachable.
    $this->drupalGet('admin/appearance/install?theme=test_theme_depending_on_modules');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalLogin($this->drupalCreateUser([
      'administer themes',
      'administer modules',
    ]));
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextNotContains('This theme requires the listed modules to operate correctly. They must first be installed by a user with permissions to do so.');
  }

  /**
   * Tests installing a theme with module dependencies.
   *
   * @param string $theme_name
   *   The name of the theme being tested.
   * @param string[] $first_modules
   *   Machine names of first modules to enable.
   * @param string[] $second_modules
   *   Machine names of second modules to enable.
   * @param string[] $required_by_messages
   *   Expected messages when attempting to uninstall $module_names.
   * @param string $base_theme_to_uninstall
   *   The name of the theme $theme_name has set as a base theme.
   * @param string[] $base_theme_module_names
   *   Machine names of the modules required by $base_theme_to_uninstall.
   *
   * @dataProvider providerTestThemeInstallWithModuleDependencies
   */
  public function testThemeInstallWithModuleDependencies($theme_name, array $first_modules, array $second_modules, array $required_by_messages, $base_theme_to_uninstall, array $base_theme_module_names): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $all_dependent_modules = array_merge($first_modules, $second_modules);
    $this->drupalGet('admin/appearance');
    $assert_module_enabled_message = function ($enabled_modules) {
      $count = count($enabled_modules);
      $module_enabled_text = $count === 1 ? "{$this->testModules[$enabled_modules[0]]} has been installed." : $count . " modules have been installed:";
      $this->assertSession()->pageTextContains($module_enabled_text);
    };
    // All the modules should be listed as disabled.
    foreach ($all_dependent_modules as $module) {
      $expected_required_list_items[$module] = $this->testModules[$module] . " (disabled)";
    }
    $this->assertUninstallableTheme($expected_required_list_items, $theme_name);

    // Enable the first group of dependee modules.
    $first_module_form_post = [];
    foreach ($first_modules as $module) {
      $first_module_form_post["modules[$module][enable]"] = 1;
    }
    $this->drupalGet('admin/modules');
    $this->submitForm($first_module_form_post, 'Install');
    $assert_module_enabled_message($first_modules);

    $this->drupalGet('admin/appearance');

    // Confirm the theme is still uninstallable due to a remaining module
    // dependency.
    // The modules that have already been enabled will no longer be listed as
    // disabled.
    foreach ($first_modules as $module) {
      $expected_required_list_items[$module] = $this->testModules[$module];
    }
    $this->assertUninstallableTheme($expected_required_list_items, $theme_name);

    // Enable the second group of dependee modules.
    $second_module_form_post = [];
    foreach ($second_modules as $module) {
      $second_module_form_post["modules[$module][enable]"] = 1;
    }
    $this->drupalGet('admin/modules');
    $this->submitForm($second_module_form_post, 'Install');
    $assert_module_enabled_message($second_modules);

    // The theme should now be installable, so install it.
    $this->drupalGet('admin/appearance');
    $page->clickLink("Install $theme_name theme");
    $assert_session->addressEquals('admin/appearance');
    $assert_session->pageTextContains("The $theme_name theme has been installed");

    // Confirm that the dependee modules can't be uninstalled because an enabled
    // theme depends on them.
    $this->drupalGet('admin/modules/uninstall');
    foreach ($all_dependent_modules as $attribute) {
      $assert_session->elementExists('css', "[name=\"uninstall[$attribute]\"][disabled]");
    }
    foreach ($required_by_messages as $selector => $message) {
      $assert_session->elementTextContains('css', $selector, $message);
    }

    // Uninstall the theme that depends on the modules, and confirm the modules
    // can now be uninstalled.
    $this->uninstallTheme($theme_name);
    $this->drupalGet('admin/modules/uninstall');

    // Only attempt to uninstall modules not required by the base theme.
    $modules_to_uninstall = array_diff($all_dependent_modules, $base_theme_module_names);
    $this->uninstallModules($modules_to_uninstall);

    if (!empty($base_theme_to_uninstall)) {
      $this->uninstallTheme($base_theme_to_uninstall);
      $this->drupalGet('admin/modules/uninstall');
      $this->uninstallModules($base_theme_module_names);
    }
  }

  /**
   * Uninstalls modules via the admin UI.
   *
   * @param string[] $module_names
   *   An array of module machine names.
   */
  protected function uninstallModules(array $module_names) {
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/modules/uninstall');
    foreach ($module_names as $attribute) {
      $assert_session->elementExists('css', "[name=\"uninstall[$attribute]\"]:not([disabled])");
    }
    $to_uninstall = [];
    foreach ($module_names as $attribute) {
      $to_uninstall["uninstall[$attribute]"] = 1;
    }
    if (!empty($to_uninstall)) {
      $this->drupalGet('admin/modules/uninstall');
      $this->submitForm($to_uninstall, 'Uninstall');
      $assert_session->pageTextContains('The following modules will be completely uninstalled from your site, and all data from these modules will be lost!');
      $assert_session->pageTextContains('Would you like to continue with uninstalling the above?');
      foreach ($module_names as $module_name) {
        $assert_session->pageTextContains($this->testModules[$module_name]);
      }
      $this->getSession()->getPage()->pressButton('Uninstall');
      $assert_session->pageTextContains('The selected modules have been uninstalled.');
    }
  }

  /**
   * Uninstalls a theme via the admin UI.
   *
   * @param string $theme_name
   *   The theme name.
   */
  protected function uninstallTheme($theme_name) {
    $this->drupalGet('admin/appearance');
    $this->clickLink("Uninstall $theme_name theme");
    $this->assertSession()->pageTextContains("The $theme_name theme has been uninstalled.");
  }

  /**
   * Data provider for testThemeInstallWithModuleDependencies().
   *
   * @return array
   *   An array of arrays. Details on the specific elements can be found in the
   *   function body.
   */
  public static function providerTestThemeInstallWithModuleDependencies() {
    // Data provider values with the following keys:
    // -'theme_name': The name of the theme being tested.
    // -'first_modules': Array of module machine names to enable first.
    // -'second_modules': Array of module machine names to enable second.
    // -'required_by_messages': Array for checking the messages explaining why a
    // module can't be uninstalled. The array key is the selector where the
    // message should appear, the array value is the expected message.
    // -'base_theme_to_uninstall': The name of a base theme that needs to be
    // uninstalled before modules it depends on can be uninstalled.
    // -'base_theme_module_names': Array of machine names of the modules
    // required by base_theme_to_uninstall.
    return [
      'test theme with a module dependency and base theme with a different module dependency' => [
        'theme_name' => 'Test Theme with a Module Dependency and Base Theme with a Different Module Dependency',
        'first_modules' => [
          'test_module_required_by_theme',
          'test_another_module_required_by_theme',
        ],
        'second_modules' => [
          'help',
        ],
        'required_by_messages' => [
          '[data-drupal-selector="edit-test-another-module-required-by-theme"] .item-list' => 'Required by the theme: Test Theme Depending on Modules',
          '[data-drupal-selector="edit-test-module-required-by-theme"] .item-list' => 'Required by the theme: Test Theme Depending on Modules',
          '[data-drupal-selector="edit-help"] .item-list' => 'Required by the theme: Test Theme with a Module Dependency and Base Theme with a Different Module Dependency',
        ],
        'base_theme_to_uninstall' => 'Test Theme Depending on Modules',
        'base_theme_module_names' => [
          'test_module_required_by_theme',
          'test_another_module_required_by_theme',
        ],
      ],
      'Test Theme Depending on Modules' => [
        'theme_name' => 'Test Theme Depending on Modules',
        'first_modules' => [
          'test_module_required_by_theme',
        ],
        'second_modules' => [
          'test_another_module_required_by_theme',
        ],
        'required_by_messages' => [
          '[data-drupal-selector="edit-test-another-module-required-by-theme"] .item-list' => 'Required by the theme: Test Theme Depending on Modules',
          '[data-drupal-selector="edit-test-module-required-by-theme"] .item-list' => 'Required by the theme: Test Theme Depending on Modules',
        ],
        'base_theme_to_uninstall' => '',
        'base_theme_module_names' => [],
      ],
      'test theme with a base theme depending on modules' => [
        'theme_name' => 'Test Theme with a Base Theme Depending on Modules',
        'first_modules' => [
          'test_module_required_by_theme',
        ],
        'second_modules' => [
          'test_another_module_required_by_theme',
        ],
        'required_by_messages' => [
          '[data-drupal-selector="edit-test-another-module-required-by-theme"] .item-list' => 'Required by the theme: Test Theme Depending on Modules',
          '[data-drupal-selector="edit-test-module-required-by-theme"] .item-list' => 'Required by the theme: Test Theme Depending on Modules',
        ],
        'base_theme_to_uninstall' => 'Test Theme Depending on Modules',
        'base_theme_module_names' => [
          'test_module_required_by_theme',
          'test_another_module_required_by_theme',
        ],
      ],
    ];
  }

  /**
   * Checks related to uninstallable themes due to module dependencies.
   *
   * @param string[] $expected_requires_list_items
   *   The modules listed as being required to install the theme.
   * @param string $theme_name
   *   The name of the theme.
   *
   * @internal
   */
  protected function assertUninstallableTheme(array $expected_requires_list_items, string $theme_name): void {
    $theme_container = $this->getSession()->getPage()->find('css', "h3:contains(\"$theme_name\")")->getParent();
    $requires_list_items = $theme_container->findAll('css', '.theme-info__requires li');
    $this->assertSameSize($expected_requires_list_items, $requires_list_items);

    foreach ($requires_list_items as $key => $item) {
      $this->assertContains($item->getText(), $expected_requires_list_items);
    }

    $incompatible = $theme_container->find('css', '.incompatible');
    $expected_incompatible_text = 'This theme requires the listed modules to operate correctly. They must first be installed via the Extend page.';
    $this->assertSame($expected_incompatible_text, $incompatible->getText());
    $this->assertFalse($theme_container->hasLink('Install Test Theme Depending on Modules theme'));
  }

  /**
   * Tests installing a theme with missing module dependencies.
   */
  public function testInstallModuleWithMissingDependencies(): void {
    $this->drupalGet('admin/appearance');
    $theme_container = $this->getSession()->getPage()->find('css', 'h3:contains("Test Theme Depending on Nonexisting Module")')->getParent();
    $this->assertStringContainsString('Requires: test_module_non_existing (missing)', $theme_container->getText());
    $this->assertStringContainsString('This theme requires the listed modules to operate correctly.', $theme_container->getText());
  }

  /**
   * Tests installing a theme with incompatible module dependencies.
   */
  public function testInstallModuleWithIncompatibleDependencies(): void {
    $this->container->get('module_installer')->install(['test_module_compatible_constraint', 'test_module_incompatible_constraint']);
    $this->drupalGet('admin/appearance');
    $theme_container = $this->getSession()->getPage()->find('css', 'h3:contains("Test Theme Depending on Version Constrained Modules")')->getParent();
    $this->assertStringContainsString('Requires: Test Module Theme Depends on with Compatible ConstraintTest Module Theme Depends on with Incompatible Constraint (>=8.x-2.x) (incompatible with version 8.x-1.8)', $theme_container->getText());
    $this->assertStringContainsString('This theme requires the listed modules to operate correctly.', $theme_container->getText());
  }

  /**
   * Tests that incompatible themes message is shown.
   */
  public function testInstalledIncompatibleTheme(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $incompatible_themes_message = 'There are errors with some installed themes. Visit the status report page for more information.';
    $path = \Drupal::getContainer()->getParameter('site.path') . "/themes/changing_theme";
    mkdir($path, 0777, TRUE);
    $file_path = "$path/changing_theme.info.yml";
    $theme_name = 'Theme that changes';
    $info = [
      'name' => $theme_name,
      'type' => 'theme',
      'base theme' => FALSE,
    ];

    $compatible_info = $info + ['core_version_requirement' => '*'];
    $incompatible_info = $info + ['core_version_requirement' => '^1'];

    $this->writeInfoFile($file_path, $compatible_info);
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextNotContains($incompatible_themes_message);
    $page->clickLink("Install $theme_name theme");
    $assert_session->addressEquals('admin/appearance');
    $assert_session->pageTextContains("The $theme_name theme has been installed");

    $this->writeInfoFile($file_path, $incompatible_info);
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextContains($incompatible_themes_message);

    $this->writeInfoFile($file_path, $compatible_info);
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextNotContains($incompatible_themes_message);

    // Uninstall the theme and ensure that incompatible themes message is not
    // displayed for themes that are not installed.
    $this->uninstallTheme($theme_name);

    $this->writeInfoFile($file_path, $incompatible_info);
    $this->drupalGet('admin/appearance');
    $this->assertSession()->pageTextNotContains($incompatible_themes_message);
  }

}
