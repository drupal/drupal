<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Utility\RequestGenerator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Utility\RequestGenerator
 * @group Utility
 */
class RequestGeneratorTest extends UnitTestCase {

  /**
   * The request generator.
   *
   * @var \Drupal\Core\Utility\RequestGenerator
   */
  protected RequestGenerator $requestGenerator;

  /**
   * The mocked path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected InboundPathProcessorInterface|ObjectProphecy $pathProcessor;

  /**
   * The mocked current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack|ObjectProphecy $currentPath;

  /**
   * The request matching mock object.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected RequestMatcherInterface|ObjectProphecy $requestMatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pathProcessor = $this->prophesize(InboundPathProcessorInterface::class);
    $this->currentPath = $this->prophesize(CurrentPathStack::class);
    $this->requestMatcher = $this->prophesize(RequestMatcherInterface::class);
    $this->requestGenerator = new RequestGenerator(
      $this->pathProcessor->reveal(),
      $this->currentPath->reveal(),
      $this->requestMatcher->reveal(),
    );
  }

  /**
   * Data provider for testGenerateRequestForPath().
   *
   * @return \Generator
   *   The test cases.
   */
  public function providerTestGenerateRequestForPath(): \Generator {
    $path = '/any/path';

    yield 'request for a path with no paths to skip' => [
      $path,
      [],
      ['processInbound' => 1, 'matchRequest' => 1],
      TRUE,
    ];

    yield 'request for a path which needs to be skipped' => [
      $path,
      [$path => TRUE],
      ['processInbound' => 0, 'matchRequest' => 0],
      FALSE,
    ];

    yield 'request for a path with other path which needs to be skipped' => [
      $path,
      ['/other/path' => TRUE],
      ['processInbound' => 1, 'matchRequest' => 1],
      TRUE,
    ];
  }

  /**
   * Tests generateRequestForPath().
   *
   * @dataProvider providerTestGenerateRequestForPath
   * @covers ::generateRequestForPath
   */
  public function testGenerateRequestForPath($path, $exclude, $methods_called, $request_generated): void {
    $route = new Route($path);
    $this->pathProcessor->processInbound($path, Argument::type(Request::class))->willReturnArgument();
    $this->requestMatcher->matchRequest(Argument::type(Request::class))->will(function ($arguments) use ($route, $path) {
      [$request] = $arguments;
      if ($request->getPathInfo() == $path) {
        return [
          RouteObjectInterface::ROUTE_NAME => 'User Example',
          RouteObjectInterface::ROUTE_OBJECT => $route,
          '_raw_variables' => new InputBag([]),
        ];
      }
    });
    $request = $this->requestGenerator->generateRequestForPath($path, $exclude);
    if ($request_generated) {
      $this->assertNotNull($request);
      $this->assertSame($request->getPathInfo(), $path);
    }
    else {
      $this->assertNull($request);
    }
  }

  /**
   * Data provider for testGenerateRequestForPathWithException().
   *
   * @return \Generator
   *   The test cases.
   */
  public function providerTestGenerateRequestForPathWithException(): \Generator {
    yield 'ParamNotConvertedException' => [ParamNotConvertedException::class, ''];
    yield 'ResourceNotFoundException' => [ResourceNotFoundException::class, ''];
    yield 'MethodNotAllowedException' => [MethodNotAllowedException::class, []];
    yield 'AccessDeniedHttpException' => [AccessDeniedHttpException::class, ''];
    yield 'NotFoundHttpException' => [NotFoundHttpException::class, ''];
  }

  /**
   * Tests generateRequestForPath() with exceptions during request matching.
   *
   * @dataProvider providerTestGenerateRequestForPathWithException
   * @covers ::generateRequestForPath
   */
  public function testGenerateRequestForPathWithException($exception_class, $exception_argument): void {
    $path = '/example';
    $exclude = [];
    $this->pathProcessor->processInbound($path, Argument::type(Request::class))->willReturnArgument();
    $this->requestMatcher->matchRequest(Argument::type(Request::class))->willThrow(new $exception_class($exception_argument));
    $request = $this->requestGenerator->generateRequestForPath($path, $exclude);
    $this->assertNull($request);
  }

}
