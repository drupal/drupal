<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests \Drupal\system\Form\ModulesListForm.
 *
 * @group Form
 */
class ModulesListFormWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system_test', 'help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::state()->set('system_test.module_hidden', FALSE);
    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
      'administer permissions',
    ]));
  }

  /**
   * Tests the module list form.
   */
  public function testModuleListForm() {
    $this->drupalGet('admin/modules');

    // Check that system_test's configure link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, '/system-test/configure/bar') and text()='Configure ']/span[contains(@class, 'visually-hidden') and text()='System test']");

    // Check that system_test's permissions link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, '/admin/people/permissions/module/system_test') and text()='Permissions ']/span[contains(@class, 'visually-hidden') and text()='for System test']");

    // Check that system_test's help link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, '/admin/help/system_test') and text()='Help ']/span[contains(@class, 'visually-hidden') and text()='for System test']");

    // Ensure that the Database Logging module's machine name is printed. This
    // module is used because its machine name is different than its human
    // readable name.
    $this->assertSession()->pageTextContains('dblog');

    // Check that the deprecated module link was rendered correctly.
    $this->assertSession()->elementExists('xpath', "//a[contains(@aria-label, 'View information on the Deprecated status of the module Deprecated module')]");
    $this->assertSession()->elementExists('xpath', "//a[contains(@href, 'http://example.com/deprecated')]");

    // Check that obsolete modules are not displayed.
    $this->assertSession()->pageTextNotContains('(Obsolete)');
  }

  /**
   * Tests the status message when enabling one or more modules.
   */
  public function testModulesListFormStatusMessage() {
    $this->drupalGet('admin/modules');

    // Enable a module that does not define permissions.
    $edit = ['modules[layout_discovery][enable]' => 'layout_discovery'];
    $this->submitForm($edit, 'Install');
    $this->assertSession()->elementTextContains('xpath', "//div[@role='contentinfo' and h2[text()='Status message']]", 'Module Layout Discovery has been enabled.');
    $this->assertSession()->elementNotExists('xpath', "//div[@role='contentinfo' and h2[text()='Status message']]//a");

    // Enable a module that defines permissions.
    $edit = ['modules[action][enable]' => 'action'];
    $this->submitForm($edit, 'Install');
    $this->assertSession()->elementTextContains('xpath', "//div[@role='contentinfo' and h2[text()='Status message']]", 'Module Actions has been enabled.');
    $this->assertSession()->elementExists('xpath', "//div[@role='contentinfo' and h2[text()='Status message']]//a[contains(@href, '/admin/people/permissions/module/action')]");

    // Enable a module that has dependencies and both define permissions.
    $edit = ['modules[content_moderation][enable]' => 'content_moderation'];
    $this->submitForm($edit, 'Install');
    $this->submitForm([], 'Continue');
    $this->assertSession()->elementTextContains('xpath', "//div[@role='contentinfo' and h2[text()='Status message']]", '2 modules have been enabled: Content Moderation, Workflows.');
    $this->assertSession()->elementExists('xpath', "//div[@role='contentinfo' and h2[text()='Status message']]//a[contains(@href, '/admin/people/permissions/module/content_moderation%2Cworkflows')]");
  }

  /**
   * Tests the module form with a module with an invalid info.yml file.
   */
  public function testModulesListFormWithInvalidInfoFile() {
    $path = \Drupal::getContainer()->getParameter('site.path') . "/modules/broken";
    mkdir($path, 0777, TRUE);
    $file_path = "$path/broken.info.yml";

    $yml = <<<BROKEN
name: Module with no core_version_requirement
type: module
BROKEN;

    file_put_contents($file_path, $yml);

    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()
      ->pageTextContains("Modules could not be listed due to an error: The 'core_version_requirement' key must be present in $file_path");

    // Check that the module filter text box is available.
    $this->assertSession()->elementExists('xpath', '//input[@name="text"]');

    unlink($file_path);
    $this->drupalGet('admin/modules');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the module filter text box is available.
    $this->assertSession()->elementExists('xpath', '//input[@name="text"]');
    $this->assertSession()->pageTextNotContains('Modules could not be listed due to an error');
  }

  /**
   * Confirm that module 'Required By' descriptions include dependent themes.
   */
  public function testRequiredByThemeMessage() {
    $this->drupalGet('admin/modules');
    $module_theme_depends_on_description = $this->getSession()->getPage()->findAll('css', '#edit-modules-test-module-required-by-theme-enable-description .admin-requirements li:contains("Test Theme Depending on Modules (theme) (disabled)")');
    // Confirm that 'Test Theme Depending on Modules' is listed as being
    // required by the module 'Test Module Required by Theme'.
    $this->assertCount(1, $module_theme_depends_on_description);
    // Confirm that the required by message does not appear anywhere else.
    $this->assertSession()->pageTextContains('Test Theme Depending on Modules (Theme) (Disabled)');
  }

  /**
   * Tests that incompatible modules message is shown.
   */
  public function testInstalledIncompatibleModule() {
    $incompatible_modules_message = 'There are errors with some installed modules. Visit the status report page for more information.';
    $path = \Drupal::getContainer()->getParameter('site.path') . "/modules/changing_module";
    mkdir($path, 0777, TRUE);
    $file_path = "$path/changing_module.info.yml";
    $info = [
      'name' => 'Module that changes',
      'type' => 'module',
    ];
    $compatible_info = $info + ['core_version_requirement' => '*'];
    $incompatible_info = $info + ['core_version_requirement' => '^1'];

    file_put_contents($file_path, Yaml::encode($compatible_info));
    $edit = ['modules[changing_module][enable]' => 'changing_module'];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->assertSession()->pageTextContains('Module Module that changes has been enabled.');

    file_put_contents($file_path, Yaml::encode($incompatible_info));
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains($incompatible_modules_message);

    file_put_contents($file_path, Yaml::encode($compatible_info));
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains($incompatible_modules_message);

    // Uninstall the module and ensure that incompatible modules message is not
    // displayed for modules that are not installed.
    $edit = ['uninstall[changing_module]' => 'changing_module'];
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');

    file_put_contents($file_path, Yaml::encode($incompatible_info));
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains($incompatible_modules_message);
  }

}
