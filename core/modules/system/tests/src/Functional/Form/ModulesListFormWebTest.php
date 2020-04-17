<?php

namespace Drupal\Tests\system\Functional\Form;

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
  public static $modules = ['system_test', 'help'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::state()->set('system_test.module_hidden', FALSE);
    $this->drupalLogin($this->drupalCreateUser(['administer modules', 'administer permissions']));
  }

  /**
   * Tests the module list form.
   */
  public function testModuleListForm() {
    $this->drupalGet('admin/modules');

    // Check that system_test's configure link was rendered correctly.
    $this->assertFieldByXPath("//a[contains(@href, '/system-test/configure/bar') and text()='Configure ']/span[contains(@class, 'visually-hidden') and text()='the System test module']");

    // Check that system_test's permissions link was rendered correctly.
    $this->assertFieldByXPath("//a[contains(@href, '/admin/people/permissions#module-system_test') and @title='Configure permissions']");

    // Check that system_test's help link was rendered correctly.
    $this->assertFieldByXPath("//a[contains(@href, '/admin/help/system_test') and @title='Help']");

    // Ensure that the Testing module's machine name is printed. Testing module
    // is used because its machine name is different than its human readable
    // name.
    $this->assertText('simpletest');
  }

  public function testModulesListFormWithInvalidInfoFile() {
    $broken_info_yml = <<<BROKEN
name: Module With Broken Info file
type: module
BROKEN;
    $path = \Drupal::service('site.path') . "/modules/broken";
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/broken.info.yml", $broken_info_yml);

    $this->drupalGet('admin/modules');

    // Confirm that the error message is shown.
    $this->assertSession()
      ->pageTextContains("The 'core' or the 'core_version_requirement' key must be present in " . $path . '/broken.info.yml');

    // Check that the module filter text box is available.
    $this->assertSession()->elementExists('xpath', '//input[@name="text"]');
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

}
