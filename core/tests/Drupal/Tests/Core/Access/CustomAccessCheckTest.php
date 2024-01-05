<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CustomAccessCheck;
use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\CustomAccessCheck
 * @group Access
 */
class CustomAccessCheckTest extends UnitTestCase {

  /**
   * The access checker to test.
   *
   * @var \Drupal\Core\Access\CustomAccessCheck
   */
  protected $accessChecker;

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $argumentsResolverFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->controllerResolver = $this->createMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->argumentsResolverFactory = $this->createMock('Drupal\Core\Access\AccessArgumentsResolverFactoryInterface');
    $this->accessChecker = new CustomAccessCheck($this->controllerResolver, $this->argumentsResolverFactory);
  }

  /**
   * Tests the access method.
   */
  public function testAccess() {
    $route_match = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->controllerResolver->expects($this->exactly(4))
      ->method('getControllerFromDefinition')
      ->willReturnMap([
        ['\Drupal\Tests\Core\Access\TestController::accessDeny', [new TestController(), 'accessDeny']],
        ['\Drupal\Tests\Core\Access\TestController::accessAllow', [new TestController(), 'accessAllow']],
        ['\Drupal\Tests\Core\Access\TestController::accessParameter', [new TestController(), 'accessParameter']],
        ['\Drupal\Tests\Core\Access\TestController::accessRequest', [new TestController(), 'accessRequest']],
      ]);

    $resolver0 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver0->expects($this->once())
      ->method('getArguments')
      ->willReturn([]);
    $resolver1 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver1->expects($this->once())
      ->method('getArguments')
      ->willReturn([]);
    $resolver2 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver2->expects($this->once())
      ->method('getArguments')
      ->willReturn(['parameter' => 'TRUE']);
    $request = Request::create('/foo?example=muh');
    $resolver3 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver3->expects($this->once())
      ->method('getArguments')
      ->willReturn(['request' => $request]);

    $this->argumentsResolverFactory->expects($this->exactly(4))
      ->method('getArgumentsResolver')
      ->willReturnOnConsecutiveCalls(
        $resolver0,
        $resolver1,
        $resolver2,
        $resolver3,
      );

    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessDeny']);
    $account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->assertEquals(AccessResult::neutral(), $this->accessChecker->access($route, $route_match, $account, $request));

    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessAllow']);
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account, $request));

    $route = new Route('/test-route', ['parameter' => 'TRUE'], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessParameter']);
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account, $request));

    $route = new Route('/test-route', ['parameter' => 'TRUE'], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessRequest']);
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account, $request));
  }

  /**
   * Tests the access method exception for invalid access callbacks.
   */
  public function testAccessException() {
    $callableResolver = $this->createMock(CallableResolver::class);
    $callableResolver->method('getCallableFromDefinition')
      ->willThrowException(new \InvalidArgumentException());

    // Re-create the controllerResolver mock with proxy to original methods.
    $this->controllerResolver = $this->getMockBuilder(ControllerResolver::class)
      ->setConstructorArgs([$callableResolver])
      ->enableProxyingToOriginalMethods()
      ->getMock();

    // Overwrite the access checker using the newly mocked controller resolve.
    $this->accessChecker = new CustomAccessCheck($this->controllerResolver, $this->argumentsResolverFactory);

    // Add a route with a _custom_access route that doesn't exist.
    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\NonExistentController::nonExistentMethod']);
    $route_match = $this->createMock(RouteMatchInterface::class);
    $account = $this->createMock(AccountInterface::class);
    $request = Request::create('/foo?example=muh');

    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('The "\Drupal\Tests\Core\Access\NonExistentController::nonExistentMethod" method is not callable as a _custom_access callback in route "/test-route"');

    // Run the access check.
    $this->accessChecker->access($route, $route_match, $account, $request);
  }

}

class TestController {

  public function accessAllow() {
    return AccessResult::allowed();
  }

  public function accessDeny() {
    return AccessResult::neutral();
  }

  public function accessParameter($parameter) {
    return AccessResult::allowedIf($parameter == 'TRUE');
  }

  public function accessRequest(Request $request) {
    return AccessResult::allowedIf($request->query->get('example') === 'muh');
  }

}
