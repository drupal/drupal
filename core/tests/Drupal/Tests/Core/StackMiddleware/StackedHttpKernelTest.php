<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\StackMiddleware\StackedHttpKernel;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Tests Drupal\Core\StackMiddleware\StackedHttpKernel.
 */
#[CoversClass(StackedHttpKernel::class)]
#[Group('StackMiddleware')]
class StackedHttpKernelTest extends UnitTestCase {

  /**
   * Tests that stacked kernel is constructed with a list of middlewares.
   */
  #[IgnoreDeprecations]
  public function testDeprecatedMiddlewaresArgument(): void {
    $request = new Request();
    $expected = new Response();
    $basicKernel = $this->createMock(HttpKernelInterface::class);
    $basicKernel->expects($this->once())
      ->method('handle')
      ->with($request, HttpKernelInterface::MAIN_REQUEST, TRUE)
      ->willReturn($expected);

    $this->expectDeprecation('Calling Drupal\Core\StackMiddleware\StackedHttpKernel::__construct() with an array of $middlewares is deprecated in drupal:11.3.0 and it will throw an error in drupal:12.0.0. Pass in a lazy iterator instead. See https://www.drupal.org/node/3538740');
    $stack = new StackedHttpKernel($basicKernel, [$basicKernel]);
    $actual = $stack->handle($request);
    $this->assertSame($expected, $actual);
  }

  /**
   * Tests that stacked kernel is constructed with a list of closures.
   */
  public function testClosureMiddlewareArgument(): void {
    $request = new Request();
    $expected = new Response();
    $basicKernel = $this->createMock(HttpKernelInterface::class);
    $basicKernel->expects($this->once())
      ->method('handle')
      ->with($request, HttpKernelInterface::MAIN_REQUEST, TRUE)
      ->willReturn($expected);

    $stack = new StackedHttpKernel($basicKernel, new \ArrayIterator([$basicKernel]));
    $actual = $stack->handle($request);
    $this->assertSame($expected, $actual);
  }

  /**
   * Tests that stacked kernel invokes the terminate call in all middlewares.
   */
  public function testTerminate(): void {
    $request = new Request();
    $response = new Response();
    $basicKernel = $this->createMockForIntersectionOfInterfaces([HttpKernelInterface::class, TerminableInterface::class]);
    $basicKernel->expects($this->once())
      ->method('terminate')
      ->with($request, $response);

    $outer = $this->createMock(HttpKernelInterface::class);
    $middle = $this->createMockForIntersectionOfInterfaces([HttpKernelInterface::class, TerminableInterface::class]);
    $middle->expects($this->once())
      ->method('terminate')
      ->with($request, $response);

    $inner = $this->createMock(HttpKernelInterface::class);

    $middlewares = new \ArrayIterator([$outer, $middle, $inner, $basicKernel]);
    $stack = new StackedHttpKernel($outer, $middlewares);
    $stack->terminate($request, $response);
  }

}
