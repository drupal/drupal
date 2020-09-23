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
   *
   * @expectedDeprecation drupal_rebuild() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use rebuild.php script instead. See https://www.drupal.org/node/3014783
   */
  public function testDrupalRebuild() {
    $before = \Drupal::service('cache.query_string')->get();
    drupal_rebuild($this->classLoader, Request::createFromGlobals());
    $this->assertNotEquals(\Drupal::service('cache.query_string')->get(), $before, 'css_js_query_string shouldn\'t be the same after rebuild');
  }

  /**
   * Tests _drupal_flush_css_js().
   *
   * @expectedDeprecation _drupal_flush_css_js() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Cache\QueryString::reset() instead. See https://www.drupal.org/node/3014783
   */
  public function testDrupalFlushCssJs() {
    $before = \Drupal::service('cache.query_string')->get();
    _drupal_flush_css_js();
    $this->assertNotEquals(\Drupal::service('cache.query_string')->get(), $before, 'css_js_query_string shouldn\'t be the same after rebuild');
  }

}
