<?php

namespace Drupal\Tests\system\Kernel\Pager;

use Drupal\KernelTests\KernelTestBase;

/**
 * Ensure that deprecated pager functions trigger deprecation errors.
 *
 * @group Pager
 * @group legacy
 */
class PagerDeprecationTest extends KernelTestBase {

  /**
   * @expectedDeprecation pager_find_page is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerParametersInterface->findPage() instead. See https://www.drupal.org/node/2779457
   */
  public function testFindPage() {
    $this->assertInternalType('int', pager_find_page());
  }

  /**
   * @expectedDeprecation pager_default_initialize is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface->createPager() instead. See https://www.drupal.org/node/2779457
   */
  public function testDefaultInitialize() {
    $this->assertInternalType('int', pager_default_initialize(1, 1));
  }

  /**
   * @expectedDeprecation pager_get_query_parameters is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerParametersInterface->getQueryParameters() instead. See https://www.drupal.org/node/2779457
   */
  public function testGetQueryParameters() {
    $this->assertInternalType('array', pager_get_query_parameters());
  }

  /**
   * @expectedDeprecation pager_query_add_page is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface->getUpdatedParameters() instead. See https://www.drupal.org/node/2779457
   */
  public function testQueryAddPage() {
    $this->assertArrayHasKey('page', pager_query_add_page([], 1, 1));
  }

}
