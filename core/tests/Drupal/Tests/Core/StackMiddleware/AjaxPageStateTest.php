<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StackMiddleware\AjaxPageState;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\StackMiddleware\AjaxPageState
 * @group StackMiddleware
 */
class AjaxPageStateTest extends UnitTestCase {

  /**
   * Tests that the query and request libraries are merged.
   *
   * @dataProvider providerHandle
   */
  public function testHandle(?string $query_libraries, ?string $request_libraries, ?string $query_expected, ?string $request_expected): void {
    $request = new Request();
    if ($query_libraries) {
      $request->query->set('ajax_page_state', ['libraries' => $query_libraries]);
    }
    if ($request_libraries) {
      $request->request->set('ajax_page_state', ['libraries' => $request_libraries]);
    }

    $result_request = new Request();
    if ($query_expected) {
      $result_request->query->set('ajax_page_state', ['libraries' => $query_expected]);
    }
    if ($request_expected) {
      $result_request->request->set('ajax_page_state', ['libraries' => $request_expected]);
    }

    $kernel = $this->prophesize(HttpKernelInterface::class);
    $kernel->handle($result_request, HttpKernelInterface::MAIN_REQUEST, TRUE)
      ->shouldBeCalled()
      ->willReturn($this->createMock(Response::class));
    $middleware = new AjaxPageState($kernel->reveal());
    $middleware->handle($request);

    // Ensure the modified request matches the expected request.
    $this->assertEquals($request->request->all(), $result_request->request->all());
    $this->assertEquals($request->query->all(), $result_request->query->all());
  }

  /**
   * Provides data for testHandle().
   */
  public static function providerHandle(): array {
    $foo_bar = UrlHelper::compressQueryParameter('foo,bar');
    $foo_baz = UrlHelper::compressQueryParameter('foo,baz');
    $data = [];
    $data['only query'] = [
      $foo_bar,
      NULL,
      'foo,bar',
      NULL,
    ];
    $data['only request'] = [
      NULL,
      $foo_bar,
      NULL,
      'foo,bar',
    ];
    $data['matching'] = [
      $foo_bar,
      $foo_bar,
      'foo,bar',
      'foo,bar',
    ];
    $data['different'] = [
      $foo_baz,
      $foo_bar,
      'foo,bar,baz',
      'foo,bar,baz',
    ];
    return $data;
  }

}
