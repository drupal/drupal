<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the rebuild script access and functionality.
 *
 * @group Rebuild
 */
class RebuildScriptTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test redirect in rebuild.php.
   */
  public function testRebuild() {
    $cache = $this->container->get('cache.default');

    $cache->set('rebuild_test', TRUE);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertUrl(new Url('<front>'));
    $this->assertInstanceOf(\stdClass::class, $cache->get('rebuild_test'));

    $settings['settings']['rebuild_access'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
    $this->rebuildAll();

    $cache->set('rebuild_test', TRUE);
    $this->drupalGet(Url::fromUri('base:core/rebuild.php'));
    $this->assertUrl(new Url('<front>'));
    $this->assertFalse($cache->get('rebuild_test'));
  }

}
