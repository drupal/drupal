<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\CustomAccessCheckTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CustomAccessCheck;
use Drupal\Core\Controller\ControllerResolver;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;

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

    $this->controllerResolver->expects($this->at(0))
      ->method('getControllerFromDefinition')
      ->with('\Drupal\Tests\Core\Access\TestController::accessDeny')
      ->will($this->returnValue([new TestController(), 'accessDeny']));

    $resolver0 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver0->expects($this->once())
      ->method('getArguments')
      ->will($this->returnValue([]));
    $this->argumentsResolverFactory->expects($this->at(0))
      ->method('getArgumentsResolver')
      ->will($this->returnValue($resolver0));

    $this->controllerResolver->expects($this->at(1))
      ->method('getControllerFromDefinition')
      ->with('\Drupal\Tests\Core\Access\TestController::accessAllow')
      ->will($this->returnValue([new TestController(), 'accessAllow']));

    $resolver1 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver1->expects($this->once())
      ->method('getArguments')
      ->will($this->returnValue([]));
    $this->argumentsResolverFactory->expects($this->at(1))
      ->method('getArgumentsResolver')
      ->will($this->returnValue($resolver1));

    $this->controllerResolver->expects($this->at(2))
      ->method('getControllerFromDefinition')
      ->with('\Drupal\Tests\Core\Access\TestController::accessParameter')
      ->will($this->returnValue([new TestController(), 'accessParameter']));

    $resolver2 = $this->createMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver2->expects($this->once())
      ->method('getArguments')
      ->will($this->returnValue(['parameter' => 'TRUE']));
    $this->argumentsResolverFactory->expects($this->at(2))
      ->method('getArgumentsResolver')
      ->will($this->returnValue($resolver2));

    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessDeny']);
    $account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->assertEquals(AccessResult::neutral(), $this->accessChecker->access($route, $route_match, $account));

    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessAllow']);
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account));

    $route = new Route('/test-route', ['parameter' => 'TRUE'], ['_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessParameter']);
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account));
  }

  /**
   * Tests the access method exception for invalid access callbacks.
   */
  public function testAccessException() {
    // Create two mocks for the ControllerResolver constructor.
    $httpMessageFactory = $this->getMockBuilder(HttpMessageFactoryInterface::class)->getMock();
    $controllerResolver = $this->getMockBuilder(ClassResolverInterface::class)->getMock();

    // Re-create the controllerResolver mock with proxy to original methods.
    $this->controllerResolver = $this->getMockBuilder(ControllerResolver::class)
      ->setConstructorArgs([$httpMessageFactory, $controllerResolver])
      ->enableProxyingToOriginalMethods()
      ->getMock();

    // Overwrite the access checker using the newly mocked controller resolve.
    $this->accessChecker = new CustomAccessCheck($this->controllerResolver, $this->argumentsResolverFactory);

    // Add a route with a _custom_access route that doesn't exist.
    $route = new Route('/test-route', [], ['_custom_access' => '\Drupal\Tests\Core\Access\NonExistentController::nonExistentMethod']);
    $route_match = $this->createMock(RouteMatchInterface::class);
    $account = $this->createMock(AccountInterface::class);

    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('The "\Drupal\Tests\Core\Access\NonExistentController::nonExistentMethod" method is not callable as a _custom_access callback in route "/test-route"');

    // Run the access check.
    $this->accessChecker->access($route, $route_match, $account);
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

}
