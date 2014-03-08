<?php

/**
 * @file
 * Contains \Drupal\field_ui\Tests\FieldUIRouteTest.
 */

namespace Drupal\field_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the functionality of the Field UI route subscriber.
 */
class FieldUIRouteTest extends WebTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('field_ui_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Field UI routes',
      'description' => 'Tests the functionality of the Field UI route subscriber.',
      'group' => 'Field UI',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->root_user);
  }

  /**
   * Ensures that entity types with bundles do not break following entity types.
   */
  public function testFieldUIRoutes() {
    $this->drupalGet('field-ui-test-no-bundle/manage/fields');
    // @todo Bring back this assertion in https://drupal.org/node/1963340.
    // @see \Drupal\field_ui\FieldOverview::getRegions()
    //$this->assertText('No fields are present yet.');

    $this->drupalGet('admin/config/people/accounts/fields');
    $this->assertTitle('Manage fields | Drupal');
    $this->assertLocalTasks();

    // Test manage display tabs and titles.
    $this->drupalGet('admin/config/people/accounts/display/compact');
    $this->assertResponse(403);

    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertTitle('Manage display | Drupal');
    $this->assertLocalTasks();

    $edit = array('display_modes_custom[compact]' => TRUE);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/config/people/accounts/display/compact');
    $this->assertTitle('Manage display | Drupal');
    $this->assertLocalTasks();

    // Test manage form display tabs and titles.
    $this->drupalGet('admin/config/people/accounts/form-display/register');
    $this->assertResponse(403);

    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertTitle('Manage form display | Drupal');
    $this->assertLocalTasks();

    $edit = array('display_modes_custom[register]' => TRUE);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/config/people/accounts/form-display/register');
    $this->assertTitle('Manage form display | Drupal');
    $this->assertLocalTasks();
    $this->assert(count($this->xpath('//ul/li[1]/a[contains(text(), :text)]', array(':text' => 'Default'))) == 1, 'Default secondary tab is in first position.');
  }

  /**
   * Asserts that local tasks exists.
   */
  public function assertLocalTasks() {
    $this->assertLink('Settings');
    $this->assertLink('Manage fields');
    $this->assertLink('Manage display');
    $this->assertLink('Manage form display');
  }

}
