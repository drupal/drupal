<?php

namespace Drupal\KernelTests\Core\Pager;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Pager
 *
 * @coversDefaultClass \Drupal\Core\Pager\PagerParameters
 */
class RequestPagerTest extends KernelTestBase {

  /**
   * @covers ::findPage
   */
  public function testFindPage() {
    $request = Request::create('http://example.com', 'GET', ['page' => '0,10']);

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    $pager_params = $this->container->get('pager.parameters');

    $this->assertEquals(10, $pager_params->findPage(1));
  }

  /**
   * @covers ::getQueryParameters
   */
  public function testGetQueryParameters() {
    $test_parameters = [
      'other' => 'arbitrary',
    ];
    $request = Request::create('http://example.com', 'GET', array_merge(['page' => '0,10'], $test_parameters));

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    $pager_params = $this->container->get('pager.parameters');

    $this->assertEquals($test_parameters, $pager_params->getQueryParameters());
    $this->assertEquals(0, $pager_params->findPage());
  }

}
