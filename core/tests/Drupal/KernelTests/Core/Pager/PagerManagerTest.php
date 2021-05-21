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
   * @covers ::getUpdatedParameters
   */
  public function testGetUpdatedParameters() {
    $element = 2;
    $index = 5;
    $test_parameters = [
      'other' => 'arbitrary',
    ];
    $request = Request::create('http://example.com', 'GET', $test_parameters);

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    /** @var \Drupal\Core\Pager\PagerManagerInterface $pager_manager */
    $pager_manager = $this->container->get('pager.manager');

    $pager_manager->createPager(30, 10, $element);
    $query = $pager_manager->getUpdatedParameters($request->query->all(), $element, $index);

    $this->assertArrayHasKey('other', $query);

    $this->assertEquals(",,$index", $query['page']);
  }

}
