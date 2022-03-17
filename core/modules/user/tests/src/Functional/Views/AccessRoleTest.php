<?php

namespace Drupal\Tests\user\Functional\Views;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\Plugin\views\access\Role;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Views;

/**
 * Tests views role access plugin.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\access\Role
 */
class AccessRoleTest extends AccessTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_access_role'];

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
   * Tests role access plugin.
   */
  public function testAccessRole() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('test_access_role');
    $display = &$view->getDisplay('default');
    $display['display_options']['access']['options']['role'] = [
      $this->normalRole => $this->normalRole,
    ];
    $view->save();
    $this->container->get('router.builder')->rebuildIfNeeded();
    $expected = [
      'config' => ['user.role.' . $this->normalRole],
      'module' => ['user', 'views_test_data'],
    ];
    $this->assertSame($expected, $view->calculateDependencies()->getDependencies());

    $executable = Views::executableFactory()->get($view);
    $executable->setDisplay('page_1');

    $access_plugin = $executable->display_handler->getPlugin('access');
    $this->assertInstanceOf(Role::class, $access_plugin);

    // Test the access() method on the access plugin.
    $this->assertFalse($executable->display_handler->access($this->webUser));
    $this->assertTrue($executable->display_handler->access($this->normalUser));

    $this->drupalLogin($this->webUser);
    $this->drupalGet('test-role');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('user.roles');

    $this->drupalLogin($this->normalUser);
    $this->drupalGet('test-role');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('user.roles');

    // Test allowing multiple roles.
    $view = Views::getView('test_access_role')->storage;
    $display = &$view->getDisplay('default');
    $display['display_options']['access']['options']['role'] = [
      $this->normalRole => $this->normalRole,
      'anonymous' => 'anonymous',
    ];
    $view->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    // Ensure that the list of roles is sorted correctly, if the generated role
    // ID comes before 'anonymous', see https://www.drupal.org/node/2398259.
    $roles = ['user.role.anonymous', 'user.role.' . $this->normalRole];
    sort($roles);
    $expected = [
      'config' => $roles,
      'module' => ['user', 'views_test_data'],
    ];
    $this->assertSame($expected, $view->calculateDependencies()->getDependencies());
    $this->drupalLogin($this->webUser);
    $this->drupalGet('test-role');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('user.roles');
    $this->drupalLogout();
    $this->drupalGet('test-role');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('user.roles');
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('test-role');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('user.roles');
  }

  /**
   * Tests access on render caching.
   */
  public function testRenderCaching() {
    $view = Views::getView('test_access_role');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['cache'] = [
      'type' => 'tag',
    ];
    $display['display_options']['access']['options']['role'] = [
      $this->normalRole => $this->normalRole,
    ];
    $view->save();

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    // First access as user with access.
    $build = DisplayPluginBase::buildBasicRenderable('test_access_role', 'default');
    $account_switcher->switchTo($this->normalUser);
    $result = $renderer->renderPlain($build);
    $this->assertContains('user.roles', $build['#cache']['contexts']);
    $this->assertEquals(['config:views.view.test_access_role'], $build['#cache']['tags']);
    $this->assertEquals(Cache::PERMANENT, $build['#cache']['max-age']);
    $this->assertNotSame('', $result);

    // Then without access.
    $build = DisplayPluginBase::buildBasicRenderable('test_access_role', 'default');
    $account_switcher->switchTo($this->webUser);
    $result = $renderer->renderPlain($build);
    // @todo Fix this in https://www.drupal.org/node/2551037,
    // DisplayPluginBase::applyDisplayCacheabilityMetadata() is not invoked when
    // using buildBasicRenderable() and a Views access plugin returns FALSE.
    // $this->assertContains('user.roles', $build['#cache']['contexts']);
    // $this->assertEquals([], $build['#cache']['tags']);
    $this->assertEquals(Cache::PERMANENT, $build['#cache']['max-age']);
    $this->assertEquals('', $result);
  }

}
