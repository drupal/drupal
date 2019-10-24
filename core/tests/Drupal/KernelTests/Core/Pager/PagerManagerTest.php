<?php

namespace Drupal\KernelTests\Core\Pager;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Pager
 *
 * @coversDefaultClass \Drupal\Core\Pager\PagerManager
 */
class PagerManagerTest extends KernelTestBase {

  /**
   * @covers ::createPager
   */
  public function testDefaultInitializeGlobals() {
    $pager_globals = [
      'pager_page_array',
      'pager_total_items',
      'pager_total',
      'pager_limits',
    ];
    foreach ($pager_globals as $pager_global) {
      $this->assertFalse(isset($GLOBALS[$pager_global]));
    }
    /* @var $pager_manager \Drupal\Core\Pager\PagerManagerInterface */
    $pager_manager = $this->container->get('pager.manager');

    $pager_manager->createPager(5, 1);

    foreach ($pager_globals as $pager_global) {
      $this->assertTrue(isset($GLOBALS[$pager_global]));
    }
  }

  /**
   * @covers ::getUpdatedParameters
   */
  public function testGetUpdatedParameters() {
    $element = 2;
    $index = 5;
    $test_parameters = [
      'other' => 'arbitrary',
    ];
    $request = Request::create('http://example.com', 'GET', $test_parameters);

    /* @var $request_stack \Symfony\Component\HttpFoundation\RequestStack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    /* @var $pager_manager \Drupal\Core\Pager\PagerManagerInterface */
    $pager_manager = $this->container->get('pager.manager');

    $pager_manager->createPager(30, 10, $element);
    $query = $pager_manager->getUpdatedParameters($request->query->all(), $element, $index);

    $this->assertArrayHasKey('other', $query);

    $this->assertEquals(",,$index", $query['page']);
  }

  /**
   * @group legacy
   * @expectedDeprecation Global variable $pager_page_array is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457
   * @expectedDeprecation Global variable $pager_total_items is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457
   * @expectedDeprecation Global variable $pager_total is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457
   * @expectedDeprecation Global variable $pager_limits is deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Pager\PagerManagerInterface instead. See https://www.drupal.org/node/2779457
   */
  public function testGlobalsSafety() {

    /* @var $pager_manager \Drupal\Core\Pager\PagerManagerInterface */
    $pager_manager = $this->container->get('pager.manager');

    $pager_manager->createPager(30, 10);

    $pager_globals = [
      'pager_page_array',
      'pager_total_items',
      'pager_total',
      'pager_limits',
    ];
    // Check globals were set.
    foreach ($pager_globals as $pager_global) {
      $this->assertTrue(isset($GLOBALS[$pager_global]));
    }

    $this->assertEquals(0, $GLOBALS['pager_page_array'][0]);
    $this->assertEquals(30, $GLOBALS['pager_total_items'][0]);
    $this->assertEquals(3, $GLOBALS['pager_total'][0]);
    $this->assertEquals(10, $GLOBALS['pager_limits'][0]);

    // Assert array is iterable.
    foreach ($GLOBALS['pager_total_items'] as $pager_id => $total_items) {
      // We only have one pager.
      $this->assertEquals(0, $pager_id);
      $this->assertEquals(30, $total_items);
    }
  }

}
