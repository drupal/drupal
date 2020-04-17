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

    // Ensure that the Database Logging module's machine name is printed. This
    // module is used because its machine name is different than its human
    // readable name.
    $this->assertText('dblog');
  }

  /**
   * Tests the module form with modules with invalid info.yml files.
   */
  public function testModulesListFormWithInvalidInfoFile() {
    $path = \Drupal::getContainer()->getParameter('site.path') . "/modules/broken";
    mkdir($path, 0777, TRUE);
    $file_path = "$path/broken.info.yml";

    $broken_infos = [
      [
        'yml' => <<<BROKEN
name: Module with no core_version_requirement or core
type: module
BROKEN,
        'expected_error' => "The 'core_version_requirement' key must be present in $file_path",
      ],
      [
        'yml' => <<<BROKEN
name: Module no core_version_requirement and invalid core
type: module
core: 9.x
BROKEN,
        // Checking for 'core_version_requirement' is done before checking
        // for a valid 'core' value.
        'expected_error' => "The 'core_version_requirement' key must be present in $file_path",
      ],
      [
        'yml' => <<<BROKEN
name: Module with core_version_requirement and invalid core
type: module
core: 9.x
core_version_requirement: ^8 || ^9
BROKEN,
        'expected_error' => "'core: 9.x' is not supported. Use 'core_version_requirement' to specify core compatibility. Only 'core: 8.x' is supported to provide backwards compatibility for Drupal 8 when needed in $file_path",
      ],
    ];

    foreach ($broken_infos as $broken_info) {
      file_put_contents($file_path, $broken_info['yml']);

      $this->drupalGet('admin/modules');
      $this->assertSession()->statusCodeEquals(200);

      $this->assertSession()
        ->pageTextContains('Modules could not be listed due to an error: ' . $broken_info['expected_error']);

      // Check that the module filter text box is available.
      $this->assertSession()->elementExists('xpath', '//input[@name="text"]');

      unlink($file_path);
      $this->drupalGet('admin/modules');
      $this->assertSession()->statusCodeEquals(200);

      // Check that the module filter text box is available.
      $this->assertSession()->elementExists('xpath', '//input[@name="text"]');
      $this->assertSession()->pageTextNotContains('Modules could not be listed due to an error');
    }
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
