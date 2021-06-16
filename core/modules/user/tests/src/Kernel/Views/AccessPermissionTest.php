<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Plugin\views\access\Permission;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests views perm access plugin.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\access\Permission
 */
class AccessPermissionTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'system',
    'user',
    'user_test_views',
    'views',
    'views_test_data',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_access_perm'];

  /**
   * A user with no special permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with 'views_test_data test permission' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create first UID1 so, the other users are not super-admin.
    $this->createUser([], NULL, FALSE, ['uid' => 1]);
    $this->webUser = $this->createUser();
    $this->normalUser = $this->createUser(['views_test_data test permission']);

    ViewTestData::createTestViews(static::class, ['user_test_views']);
  }

  /**
   * Tests perm access plugin.
   */
  public function testAccessPerm() {
    $view = Views::getView('test_access_perm');
    $view->setDisplay();

    $access_plugin = $view->display_handler->getPlugin('access');
    $this->assertInstanceOf(Permission::class, $access_plugin);
    $this->assertEquals('Permission', $access_plugin->pluginTitle());

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

    $renderer = $this->container->get('renderer');
    $account_switcher = $this->container->get('account_switcher');

    // First access as user without access.
    $build = DisplayPluginBase::buildBasicRenderable('test_access_perm', 'default');
    $account_switcher->switchTo($this->webUser);
    $this->assertEmpty($renderer->renderPlain($build));

    // Then with access.
    $build = DisplayPluginBase::buildBasicRenderable('test_access_perm', 'default');
    $account_switcher->switchTo($this->normalUser);
    $this->assertNotEmpty($renderer->renderPlain($build));
  }

}
