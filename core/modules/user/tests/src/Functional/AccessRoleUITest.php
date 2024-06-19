<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\views_ui\Functional\UITestBase;

/**
 * Tests views role access plugin UI.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\access\Role
 */
class AccessRoleUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_access_role'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'user_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['user_test_views']): void {
    parent::setUp($import_test_views, $modules);
  }

  /**
   * Tests the role access plugin UI.
   */
  public function testAccessRoleUI(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('user_role')->create(['id' => 'custom_role', 'label' => 'Custom role'])->save();
    $access_url = "admin/structure/views/nojs/display/test_access_role/default/access_options";
    $this->drupalGet($access_url);
    $this->submitForm(['access_options[role][custom_role]' => 1], 'Apply');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([], 'Save');
    $view = $entity_type_manager->getStorage('view')->load('test_access_role');

    $display = $view->getDisplay('default');
    $this->assertEquals(['custom_role' => 'custom_role'], $display['display_options']['access']['options']['role']);

    // Test changing access plugin from role to none.
    $this->drupalGet('admin/structure/views/nojs/display/test_access_role/default/access');
    $this->submitForm(['access[type]' => 'none'], 'Apply');
    $this->submitForm([], 'Save');
    // Verify that role option is not set.
    $view = $entity_type_manager->getStorage('view')->load('test_access_role');
    $display = $view->getDisplay('default');
    $this->assertFalse(isset($display['display_options']['access']['options']['role']));
  }

}
