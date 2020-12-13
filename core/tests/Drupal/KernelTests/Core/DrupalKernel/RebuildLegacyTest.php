<?php

namespace Drupal\KernelTests\Core\DrupalKernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests utility.inc functions.
 *
 * @group legacy
 */
class RebuildLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    include_once $this->root . '/core/includes/utility.inc';
  }

  /**
   * Tests drupal_rebuild().
   */
  public function testDrupalRebuild() {
    $this->expectDeprecation('drupal_rebuild() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use rebuild.php script instead. See https://www.drupal.org/node/3014783');
    $before = \Drupal::service('cache.query_string')->get();
    drupal_rebuild($this->classLoader, Request::createFromGlobals());
    $this->assertNotEquals(\Drupal::service('cache.query_string')->get(), $before, 'css_js_query_string shouldn\'t be the same after rebuild');
  }

  /**
   * Tests _drupal_flush_css_js().
   */
  public function testDrupalFlushCssJs() {
    $this->expectDeprecation('_drupal_flush_css_js() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Cache\QueryString::reset() instead. See https://www.drupal.org/node/3014783');
    $before = \Drupal::service('cache.query_string')->get();
    _drupal_flush_css_js();
    $this->assertNotEquals(\Drupal::service('cache.query_string')->get(), $before, 'css_js_query_string shouldn\'t be the same after rebuild');
  }

  /**
   * Tests drupal_flush_all_caches().
   */
  public function testDrupalFlushAllCaches() {
    $this->expectDeprecation('drupal_flush_all_caches() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Cache\Rebuilder::rebuildAll() instead. See https://www.drupal.org/node/3014783');
    drupal_flush_all_caches();
  }
}
