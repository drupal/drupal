<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\CustomAccessCheckTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CustomAccessCheck;
use Drupal\Tests\UnitTestCase;
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
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * The mocked arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $argumentsResolverFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->controllerResolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
    $this->argumentsResolverFactory = $this->getMock('Drupal\Core\Access\AccessArgumentsResolverFactoryInterface');
    $this->accessChecker = new CustomAccessCheck($this->controllerResolver, $this->argumentsResolverFactory);
  }

  /**
   * Test the access method.
   */
  public function testAccess() {
    $route_match = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');

    $this->controllerResolver->expects($this->at(0))
      ->method('getControllerFromDefinition')
      ->with('\Drupal\Tests\Core\Access\TestController::accessDeny')
      ->will($this->returnValue(array(new TestController(), 'accessDeny')));

    $resolver0 = $this->getMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver0->expects($this->once())
      ->method('getArguments')
      ->will($this->returnValue(array()));
    $this->argumentsResolverFactory->expects($this->at(0))
      ->method('getArgumentsResolver')
      ->will($this->returnValue($resolver0));

    $this->controllerResolver->expects($this->at(1))
      ->method('getControllerFromDefinition')
      ->with('\Drupal\Tests\Core\Access\TestController::accessAllow')
      ->will($this->returnValue(array(new TestController(), 'accessAllow')));

    $resolver1 = $this->getMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver1->expects($this->once())
      ->method('getArguments')
      ->will($this->returnValue(array()));
    $this->argumentsResolverFactory->expects($this->at(1))
      ->method('getArgumentsResolver')
      ->will($this->returnValue($resolver1));

    $this->controllerResolver->expects($this->at(2))
      ->method('getControllerFromDefinition')
      ->with('\Drupal\Tests\Core\Access\TestController::accessParameter')
      ->will($this->returnValue(array(new TestController(), 'accessParameter')));

    $resolver2 = $this->getMock('Drupal\Component\Utility\ArgumentsResolverInterface');
    $resolver2->expects($this->once())
      ->method('getArguments')
      ->will($this->returnValue(array('parameter' => 'TRUE')));
    $this->argumentsResolverFactory->expects($this->at(2))
      ->method('getArgumentsResolver')
      ->will($this->returnValue($resolver2));

    $route = new Route('/test-route', array(), array('_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessDeny'));
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->assertEquals(AccessResult::neutral(), $this->accessChecker->access($route, $route_match, $account));

    $route = new Route('/test-route', array(), array('_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessAllow'));
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account));

    $route = new Route('/test-route', array('parameter' => 'TRUE'), array('_custom_access' => '\Drupal\Tests\Core\Access\TestController::accessParameter'));
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $route_match, $account));
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
