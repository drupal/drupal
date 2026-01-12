<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Pager;

use Drupal\Core\Pager\PagerParameters;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests Drupal\Core\Pager\PagerParameters.
 */
#[CoversClass(PagerParameters::class)]
#[Group('Pager')]
#[RunTestsInSeparateProcesses]
class RequestPagerTest extends KernelTestBase {

  /**
   * Tests find page.
   */
  public function testFindPage(): void {
    $request = Request::create('http://example.com', 'GET', ['page' => '0,10']);
    $request->setSession(new Session(new MockArraySessionStorage()));

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    $pager_params = $this->container->get('pager.parameters');

    $this->assertEquals(10, $pager_params->findPage(1));
  }

  /**
   * Tests get query parameters.
   */
  public function testGetQueryParameters(): void {
    $test_parameters = [
      'other' => 'arbitrary',
    ];
    $request = Request::create('http://example.com', 'GET', array_merge(['page' => '0,10'], $test_parameters));
    $request->setSession(new Session(new MockArraySessionStorage()));

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->push($request);

    $pager_params = $this->container->get('pager.parameters');

    $this->assertEquals($test_parameters, $pager_params->getQueryParameters());
    $this->assertEquals(0, $pager_params->findPage());
  }

}
