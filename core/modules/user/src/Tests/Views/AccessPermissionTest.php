<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\AccessPermissionTest.
 */

namespace Drupal\user\Tests\Views;

use Drupal\user\Plugin\views\access\Permission;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Views;

/**
 * Tests views perm access plugin.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\access\Permission
 */
class AccessPermissionTest extends AccessTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_access_perm');

  /**
   * Tests perm access plugin.
   */
  function testAccessPerm() {
    $view = Views::getView('test_access_perm');
    $view->setDisplay();

    $access_plugin = $view->display_handler->getPlugin('access');
    $this->assertTrue($access_plugin instanceof Permission, 'Make sure the right class got instantiated.');
    $this->assertEqual($access_plugin->pluginTitle(), t('Permission'));

    $this->assertFalse($view->display_handler->access($this->webUser));
    $this->assertTrue($view->display_handler->access($this->normalUser));
  }

  /**
   * Tests access on render caching.
   */
  public function testRenderCaching() {
    $view = Views::getView('test_access_perm');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['cache'] = [
      'type' => 'tag',
    ];

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    // First access as user without access.
    $build = DisplayPluginBase::buildBasicRenderable('test_access_perm', 'default');
    $account_switcher->switchTo($this->normalUser);
    $result = $renderer->renderPlain($build);
    $this->assertNotEqual($result, '');

    // Then with access.
    $build = DisplayPluginBase::buildBasicRenderable('test_access_perm', 'default');
    $account_switcher->switchTo($this->webUser);
    $result = $renderer->renderPlain($build);
    $this->assertEqual($result, '');
  }

}
