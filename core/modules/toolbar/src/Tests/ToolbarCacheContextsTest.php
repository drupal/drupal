<?php

/**
 * @file
 * Contains \Drupal\toolbar\Tests\ToolbarCacheContextsTest.
 */

namespace Drupal\toolbar\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;


/**
 * Tests the cache contexts for toolbar.
 *
 * @group toolbar
 */
class ToolbarCacheContextsTest extends WebTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['toolbar', 'test_page_test'];

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
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->perms);
    $this->adminUser2 = $this->drupalCreateUser($this->perms);
  }

  /**
   * Tests toolbar cache contexts.
   */
  public function testToolbarCacheContextsCaller() {
    // Test with default combination and permission to see toolbar.
    $this->assertToolbarCacheContexts(['user'], 'Expected cache contexts found for default combination and permission to see toolbar.');

    // Test without user toolbar tab. User module is a required module so we have to
    // manually remove the user toolbar tab.
    $this->installExtraModules(['toolbar_disable_user_toolbar']);
    $this->assertToolbarCacheContexts(['user.permissions'], 'Expected cache contexts found without user toolbar tab.');

    // Test with the toolbar and contextual enabled.
    $this->installExtraModules(['contextual']);
    $this->adminUser2 = $this->drupalCreateUser(array_merge($this->perms, ['access contextual links']));
    $this->assertToolbarCacheContexts(['user.permissions'], 'Expected cache contexts found with contextual module enabled.');
    \Drupal::service('module_installer')->uninstall(['contextual']);

    // Test with the tour module enabled.
    $this->installExtraModules(['tour']);
    $this->adminUser2 = $this->drupalCreateUser(array_merge($this->perms, ['access tour']));
    $this->assertToolbarCacheContexts(['user.permissions'], 'Expected cache contexts found with tour module enabled.');
    \Drupal::service('module_installer')->uninstall(['tour']);

    // Test with shortcut module enabled.
    $this->installExtraModules(['shortcut']);
    $this->adminUser2 = $this->drupalCreateUser(array_merge($this->perms, ['access shortcuts', 'administer shortcuts']));
    $this->assertToolbarCacheContexts(['user'], 'Expected cache contexts found with shortcut module enabled.');
  }

  /**
   * Tests that cache contexts are applied for both users.
   *
   * @param string[] $cache_contexts
   *   Expected cache contexts for both users.
   * @param string $message
   *   (optional) A verbose message to output.
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertToolbarCacheContexts(array $cache_contexts, $message = NULL) {
    // Default cache contexts that should exist on all test cases.
    $default_cache_contexts = [
      'languages:language_interface',
      'theme',
    ];
    $cache_contexts = Cache::mergeContexts($default_cache_contexts, $cache_contexts);

    // Assert contexts for user1 which has only default permissions.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-page');
    $return = $this->assertCacheContexts($cache_contexts);
    $this->drupalLogout();

    // Assert contexts for user2 which has some additional permissions.
    $this->drupalLogin($this->adminUser2);
    $this->drupalGet('test-page');
    $return = $return && $this->assertCacheContexts($cache_contexts);

    if ($return) {
      $this->pass($message);
    }
    else {
      $this->fail($message);
    }
    return $return;
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
