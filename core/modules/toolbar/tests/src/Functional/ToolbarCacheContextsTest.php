<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the cache contexts for toolbar.
 *
 * @group toolbar
 */
class ToolbarCacheContextsTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['toolbar', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An authenticated user to use for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An authenticated user to use for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser2;

  /**
   * A list of default permissions for test users.
   *
   * @var array
   */
  protected $perms = [
    'access toolbar',
    'access administration pages',
    'administer site configuration',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->perms);
    $this->adminUser2 = $this->drupalCreateUser($this->perms);
  }

  /**
   * Tests toolbar cache integration.
   */
  public function testCacheIntegration(): void {
    $this->installExtraModules(['csrf_test', 'dynamic_page_cache']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-page');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->assertCacheContexts(['session', 'user', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT], 'Expected cache contexts found with CSRF token link.');
    $this->drupalGet('test-page');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');
    $this->assertCacheContexts(['session', 'user', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT], 'Expected cache contexts found with CSRF token link.');
  }

  /**
   * Tests toolbar cache contexts.
   */
  public function testToolbarCacheContextsCaller(): void {
    // Test with default combination and permission to see toolbar.
    $this->assertToolbarCacheContexts(['user', 'session'], 'Expected cache contexts found for default combination and permission to see toolbar.');

    // Test without user toolbar tab. User module is a required module so we have to
    // manually remove the user toolbar tab.
    $this->installExtraModules(['toolbar_disable_user_toolbar']);
    $this->assertToolbarCacheContexts(['user.permissions'], 'Expected cache contexts found without user toolbar tab.');

    // Test with the toolbar and contextual enabled.
    $this->installExtraModules(['contextual']);
    $this->adminUser2 = $this->drupalCreateUser(array_merge($this->perms, ['access contextual links']));
    $this->assertToolbarCacheContexts(['user.permissions'], 'Expected cache contexts found with contextual module enabled.');
    \Drupal::service('module_installer')->uninstall(['contextual']);

    // Test with the comment module enabled.
    $this->installExtraModules(['comment']);
    $this->adminUser2 = $this->drupalCreateUser(array_merge($this->perms, ['access comments']));
    $this->assertToolbarCacheContexts(['user.permissions'], 'Expected cache contexts found with comment module enabled.');
    \Drupal::service('module_installer')->uninstall(['comment']);
  }

  /**
   * Tests that cache contexts are applied for both users.
   *
   * @param string[] $cache_contexts
   *   Expected cache contexts for both users.
   * @param string $message
   *   (optional) A verbose message to output.
   *
   * @internal
   */
  protected function assertToolbarCacheContexts(array $cache_contexts, ?string $message = NULL): void {
    // Default cache contexts that should exist on all test cases.
    $default_cache_contexts = [
      'languages:language_interface',
      'theme',
      'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT,
    ];
    $cache_contexts = Cache::mergeContexts($default_cache_contexts, $cache_contexts);

    // Assert contexts for user1 which has only default permissions.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-page');
    $this->assertCacheContexts($cache_contexts, $message);
    $this->drupalLogout();

    // Assert contexts for user2 which has some additional permissions.
    $this->drupalLogin($this->adminUser2);
    $this->drupalGet('test-page');
    $this->assertCacheContexts($cache_contexts, $message);
  }

  /**
   * Installs a given list of modules and rebuilds the cache.
   *
   * @param string[] $module_list
   *   An array of module names.
   */
  protected function installExtraModules(array $module_list) {
    \Drupal::service('module_installer')->install($module_list);

    // Installing modules updates the container and needs a router rebuild.
    $this->container = \Drupal::getContainer();
    $this->container->get('router.builder')->rebuildIfNeeded();
  }

}
