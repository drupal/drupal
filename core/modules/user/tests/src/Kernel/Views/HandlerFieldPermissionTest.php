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
  public static $testViews = ['test_field_permission'];

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

    $expected_permissions = [];
    $expected_permissions[$this->users[0]->id()] = [];
    $expected_permissions[$this->users[1]->id()] = [];
    $expected_permissions[$this->users[2]->id()][] = t('Administer roles and permissions');
    // View user profiles comes first, because we sort by the permission
    // machine name.
    $expected_permissions[$this->users[3]->id()][] = t('View user information');
    $expected_permissions[$this->users[3]->id()][] = t('Administer roles and permissions');
    $expected_permissions[$this->users[3]->id()][] = t('Administer users');

    foreach ($view->result as $index => $row) {
      $uid = $view->field['uid']->getValue($row);
      $rendered_permission = $style_plugin->getField($index, 'permission');

      $expected_output = implode(', ', $expected_permissions[$uid]);
      $this->assertEqual($expected_output, $rendered_permission, 'The right permissions are rendered.');
    }
  }

}
