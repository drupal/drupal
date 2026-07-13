<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessArgumentsResolverFactoryInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CustomAccessCheck;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\Core\Access\CustomAccessCheck.
 */
#[CoversClass(CustomAccessCheck::class)]
#[Group('Access')]
class CustomAccessCheckTest extends UnitTestCase {

  /**
   * Tests the access method.
   */
  public function testAccess(): void {
    $callableResolver = $this->createMock(CallableResolver::class);
    $argumentsResolverFactory = $this->createMock(AccessArgumentsResolverFactoryInterface::class);
    $accessChecker = new CustomAccessCheck($callableResolver, $argumentsResolverFactory);

    $route_match = $this->createStub(RouteMatchInterface::class);

    $callableResolver
      ->expects($this->exactly(4))
      ->method('getCallableFromDefinition')
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

    $argumentsResolverFactory->expects($this->exactly(4))
      ->method('getArgumentsResolver')
      ->willReturnOnConsecutiveCalls(
        $resolver0,
        $resolver1,
        $resolver2,
        $resolver3,
      );

    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessDeny']);
    $account = $this->createStub(AccountInterface::class);
    $this->assertEquals(AccessResult::neutral(), $accessChecker->access($route, $route_match, $account, $request));

    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessAllow']);
    $this->assertEquals(AccessResult::allowed(), $accessChecker->access($route, $route_match, $account, $request));

    $route = new Route('/test-route', ['parameter' => 'TRUE'], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessParameter']);
    $this->assertEquals(AccessResult::allowed(), $accessChecker->access($route, $route_match, $account, $request));

    $route = new Route('/test-route', ['parameter' => 'TRUE'], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessRequest']);
    $this->assertEquals(AccessResult::allowed(), $accessChecker->access($route, $route_match, $account, $request));
  }

  /**
   * Tests the access method exception for invalid access callbacks.
   */
  public function testAccessException(): void {
    // Create callableResolver mock to return InvalidArgumentException.
    $callableResolver = $this->createStub(CallableResolver::class);

    $callableResolver
      ->method('getCallableFromDefinition')
      ->willThrowException(new \InvalidArgumentException());

    $accessChecker = new CustomAccessCheck($callableResolver, $this->createStub(AccessArgumentsResolverFactoryInterface::class));

    // Add a route with a _custom_access route that doesn't exist.
    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\NonExistentController::nonExistentMethod']);
    $route_match = $this->createStub(RouteMatchInterface::class);
    $account = $this->createStub(AccountInterface::class);
    $request = Request::create('/foo?example=muh');

    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessageIs('The "\Drupal\Tests\Core\Access\NonExistentController::nonExistentMethod" method is not callable as a _custom_access callback in route "/test-route"');

    // Run the access check.
    $accessChecker->access($route, $route_match, $account, $request);
  }

}

/**
 * Controller for testing custom access.
 */
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
