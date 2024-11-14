<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the rebuild script access and functionality.
 *
 * @group Rebuild
 */
class RebuildScriptTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['module_test', 'container_rebuild_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests redirect in rebuild.php.
   */
  public function testRebuild(): void {
    $cache = $this->container->get('cache.default');

    $cache->set('rebuild_test', TRUE);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->addressEquals(new Url('<front>'));
    $this->assertInstanceOf(\stdClass::class, $cache->get('rebuild_test'));

    $settings['settings']['rebuild_access'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
    $this->rebuildAll();

    $cache->set('rebuild_test', TRUE);
    \Drupal::state()->set('container_rebuild_test.count', 0);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->addressEquals(new Url('<front>'));
    $this->assertFalse($cache->get('rebuild_test'));
    $this->refreshVariables();
    $this->assertSame(1, \Drupal::state()->get('container_rebuild_test.count', 0));
    $this->drupalGet('/container_rebuild_test/module_test/system_info_alter');
    $this->assertSession()->pageTextContains('module_test: core/modules/system/tests/modules/module_test');
    $this->assertSession()->pageTextContains('system_info_alter: true');

    // Move a module to ensure it does not break the rebuild.
    $file_system = new Filesystem();
    $file_system->mirror('core/modules/system/tests/modules/module_test', $this->siteDirectory . '/modules/module_test');
    \Drupal::state()->set('container_rebuild_test.count', 0);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->addressEquals(new Url('<front>'));
    $this->refreshVariables();
    $this->assertSame(1, \Drupal::state()->get('container_rebuild_test.count', 0));
    $this->drupalGet('/container_rebuild_test/module_test/system_info_alter');
    $this->assertSession()->pageTextContains('module_test: ' . $this->siteDirectory . '/modules/module_test');
    $this->assertSession()->pageTextContains('system_info_alter: true');

    // Disable a module by writing to the core.extension list.
    $this->config('core.extension')->clear('module.module_test')->save();
    \Drupal::state()->set('container_rebuild_test.count', 0);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->addressEquals(new Url('<front>'));
    $this->refreshVariables();
    $this->assertSame(1, \Drupal::state()->get('container_rebuild_test.count', 0));
    $this->drupalGet('/container_rebuild_test/module_test/system_info_alter');
    $this->assertSession()->pageTextContains('module_test: not installed');
    $this->assertSession()->pageTextContains('system_info_alter: false');

    // Enable a module by writing to the core.extension list.
    $modules = $this->config('core.extension')->get('module');
    $modules['module_test'] = 0;
    $this->config('core.extension')->set('module', module_config_sort($modules))->save();
    \Drupal::state()->set('container_rebuild_test.count', 0);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->addressEquals(new Url('<front>'));
    $this->refreshVariables();
    $this->assertSame(1, \Drupal::state()->get('container_rebuild_test.count', 0));
    $this->drupalGet('/container_rebuild_test/module_test/system_info_alter');
    $this->assertSession()->pageTextContains('module_test: ' . $this->siteDirectory . '/modules/module_test');
    $this->assertSession()->pageTextContains('system_info_alter: true');

    // Test how many container rebuild occur when there is no cached container.
    \Drupal::state()->set('container_rebuild_test.count', 0);
    \Drupal::service('kernel')->invalidateContainer();
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertSession()->addressEquals(new Url('<front>'));
    $this->assertFalse($cache->get('rebuild_test'));
    $this->refreshVariables();
    $this->assertSame(1, \Drupal::state()->get('container_rebuild_test.count', 0));
  }

}
