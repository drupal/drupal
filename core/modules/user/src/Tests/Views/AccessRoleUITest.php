<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\AccessRoleUITest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Tests\ViewTestData;
use Drupal\views_ui\Tests\UITestBase;

/**
 * Tests views role access plugin UI.
 *
 * @group user
 * @see Drupal\user\Plugin\views\access\Role
 */
class AccessRoleUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_access_role');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'user_test_views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('user_test_views'));
  }

  /**
   * Tests the role access plugin UI.
   */
  public function testAccessRoleUI() {
    $entity_manager = $this->container->get('entity.manager');
    $entity_manager->getStorage('user_role')->create(array('id' => 'custom_role', 'label' => 'Custom role'))->save();
    $access_url = "admin/structure/views/nojs/display/test_access_role/default/access_options";
    $this->drupalPostForm($access_url, array('access_options[role][custom_role]' => 1), t('Apply'));
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, array(), t('Save'));
    $view = $entity_manager->getStorage('view')->load('test_access_role');

    $display = $view->getDisplay('default');
    $this->assertEqual($display['display_options']['access']['options']['role'], array('custom_role' => 'custom_role'));

    // Test changing access plugin from role to none.
    $this->drupalPostForm('admin/structure/views/nojs/display/test_access_role/default/access', ['access[type]' => 'none'], t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Save'));
    // Verify that role option is not set.
    $view = $entity_manager->getStorage('view')->load('test_access_role');
    $display = $view->getDisplay('default');
    $this->assertFalse(isset($display['display_options']['access']['options']['role']));
  }

}
