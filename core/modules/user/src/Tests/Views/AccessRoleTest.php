<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\AccessRoleTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\user\Plugin\views\access\Role;
use Drupal\views\Views;
use Drupal\views\ViewStorageInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests views role access plugin.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\access\Role
 */
class AccessRoleTest extends AccessTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_access_role');

  /**
   * Tests role access plugin.
   */
  function testAccessRole() {
    /** @var \Drupal\views\ViewStorageInterface $view */
    $view = \Drupal::entityManager()->getStorage('view')->load('test_access_role');
    $display = &$view->getDisplay('default');
    $display['display_options']['access']['options']['role'] = array(
      $this->normalRole => $this->normalRole,
    );
    $view->save();

    $executable = Views::executableFactory()->get($view);
    $executable->setDisplay('page_1');

    $access_plugin = $executable->display_handler->getPlugin('access');
    $this->assertTrue($access_plugin instanceof Role, 'Make sure the right class got instantiated.');

    // Test the access() method on the access plugin.
    $this->assertTrue($executable->display_handler->access($this->adminUser), 'Admin-Account should be able to access the view everytime');
    $this->assertFalse($executable->display_handler->access($this->webUser));
    $this->assertTrue($executable->display_handler->access($this->normalUser));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-role');
    $this->assertResponse(200);

    $this->drupalLogin($this->webUser);
    $this->drupalGet('test-role');
    $this->assertResponse(403);

    $this->drupalLogin($this->normalUser);
    $this->drupalGet('test-role');
    $this->assertResponse(200);

    // Test allowing multiple roles.
    $view = Views::getView('test_access_role')->storage;
    $display = &$view->getDisplay('default');
    $display['display_options']['access']['options']['role'] = array(
      $this->normalRole => $this->normalRole,
      'anonymous' => 'anonymous',
    );
    $view->save();
    $this->drupalLogin($this->webUser);
    $this->drupalGet('test-role');
    $this->assertResponse(403);
    $this->drupalLogout();
    $this->drupalGet('test-role');
    $this->assertResponse(200);
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('test-role');
    $this->assertResponse(200);
  }

}
