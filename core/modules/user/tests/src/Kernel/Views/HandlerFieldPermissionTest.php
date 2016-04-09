<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\views\Views;

/**
 * Tests the permission field handler.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\field\Permissions
 */
class HandlerFieldPermissionTest extends UserKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_field_permission');

  /**
   * Tests the permission field handler output.
   */
  public function testFieldPermission() {
    $this->setupPermissionTestData();

    $view = Views::getView('test_field_permission');
    $this->executeView($view);
    $view->initStyle();
    $view->render();
    $style_plugin = $view->style_plugin;

    $expected_permissions = array();
    $expected_permissions[$this->users[0]->id()] = array();
    $expected_permissions[$this->users[1]->id()] = array();
    $expected_permissions[$this->users[2]->id()][] = t('Administer permissions');
    // View user profiles comes first, because we sort by the permission
    // machine name.
    $expected_permissions[$this->users[3]->id()][] = t('Administer permissions');
    $expected_permissions[$this->users[3]->id()][] = t('Administer users');
    $expected_permissions[$this->users[3]->id()][] = t('View user information');

    foreach ($view->result as $index => $row) {
      $uid = $view->field['uid']->getValue($row);
      $rendered_permission = $style_plugin->getField($index, 'permission');

      $expected_output = implode(', ', $expected_permissions[$uid]);
      $this->assertEqual($rendered_permission, $expected_output, 'The right permissions are rendered.');
    }
  }

}
