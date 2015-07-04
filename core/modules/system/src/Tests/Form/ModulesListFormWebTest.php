<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\ModulesListFormWebTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests \Drupal\system\Form\ModulesListForm.
 *
 * @group Form
 */
class ModulesListFormWebTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('system_test', 'help');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::state()->set('system_test.module_hidden', FALSE);
  }

  /**
   * Tests the module list form.
   */
  public function testModuleListForm() {
    $this->drupalLogin(
      $this->drupalCreateUser(
        array('administer modules', 'administer permissions')
      )
    );
    $this->drupalGet('admin/modules');
    $this->assertResponse('200');

    // Check that system_test's configure link was rendered correctly.
    $this->assertFieldByXPath("//a[contains(@href, '/system-test/configure/bar') and @title='Bar.bar']");

    // Check that system_test's permissions link was rendered correctly.
    $this->assertFieldByXPath("//a[contains(@href, '/admin/people/permissions#module-system_test') and @title='Configure permissions']");

    // Check that system_test's help link was rendered correctly.
    $this->assertFieldByXPath("//a[contains(@href, '/admin/help/system_test') and @title='Help']");
  }

}
