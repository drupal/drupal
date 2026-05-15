<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\ExceptionStatusCodeCacheContext;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Tests Drupal\Core\Cache\Context\CookiesCacheContext.
 */
#[CoversClass(ExceptionStatusCodeCacheContext::class)]
#[Group('Cache')]
class ExceptionStatusCodeCacheContextTest extends UnitTestCase {

  /**
   * Tests get context.
   */
  #[DataProvider('providerTestGetContext')]
  public function testGetContext(?\Exception $exception, string $result): void {
    $request_stack = new RequestStack();
    $request = Request::create('/', 'GET');
    if (isset($exception)) {
      $request->attributes->set('exception', $exception);
    }
    $request_stack->push($request);
    $cache_context = new ExceptionStatusCodeCacheContext($request_stack);
    $this->assertSame($cache_context->getContext(), $result);
  }

  /**
   * Provides a list of cookies and expected cache contexts.
   */
  public static function providerTestGetContext(): array {
    return [
      [new NotFoundHttpException(), '404'],
      [new AccessDeniedHttpException(), '403'],
      [new BadRequestHttpException(), '400'],
      [new MethodNotAllowedHttpException(['POST']), '405'],
      [NULL, '0'],
    ];
  }

}
