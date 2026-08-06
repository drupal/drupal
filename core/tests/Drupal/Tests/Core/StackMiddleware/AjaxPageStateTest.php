<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\StackMiddleware\AjaxPageState;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\StackMiddleware\AjaxPageState.
 */
#[CoversClass(AjaxPageState::class)]
#[Group('StackMiddleware')]
class AjaxPageStateTest extends UnitTestCase {

  /**
   * Tests that the query and request libraries are merged.
   */
  #[DataProvider('providerHandle')]
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
      $result_request->attributes->set('ajax_page_state', ['libraries' => $query_expected]);
    }
    if ($request_expected) {
      $result_request->request->set('ajax_page_state', ['libraries' => $request_expected]);
      $result_request->attributes->set('ajax_page_state', ['libraries' => $request_expected]);
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
    $this->assertEquals($request->attributes->all(), $result_request->attributes->all());
  }

  /**
   * Provides data for testHandle().
   */
  public static function providerHandle(): array {
    $foo_bar = UrlHelper::compressQueryParameter('core/foo,core/bar');
    $foo_baz = UrlHelper::compressQueryParameter('core/foo,core/baz');
    $data = [];
    $data['only query'] = [
      $foo_bar,
      NULL,
      'core/foo,core/bar',
      NULL,
    ];
    $data['only request'] = [
      NULL,
      $foo_bar,
      NULL,
      'core/foo,core/bar',
    ];
    $data['matching'] = [
      $foo_bar,
      $foo_bar,
      'core/foo,core/bar',
      'core/foo,core/bar',
    ];
    $data['different'] = [
      $foo_baz,
      $foo_bar,
      'core/foo,core/bar,core/baz',
      'core/foo,core/bar,core/baz',
    ];
    $data['invalid libraries discarded'] = [
      UrlHelper::compressQueryParameter('<b>foo'),
      NULL,
      NULL,
      NULL,
    ];
    $data['mixed valid and invalid'] = [
      UrlHelper::compressQueryParameter('core/drupal,<b>foo'),
      NULL,
      'core/drupal',
      NULL,
    ];
    // A name containing a slash is structurally valid; the middleware does not
    // need to filter it because the asset system ignores unknown libraries.
    $data['slash-containing name preserved'] = [
      UrlHelper::compressQueryParameter('core/<b>foo'),
      NULL,
      'core/<b>foo',
      NULL,
    ];
    return $data;
  }

}
