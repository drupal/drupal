<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\AccessAwareRouter;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Routing\AccessAwareRouter
 * @group Routing
 */
class AccessAwareRouterTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;

  /**
   * @var \Symfony\Cmf\Component\Routing\ChainRouter|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $chainRouter;

  /**
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface||\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Routing\AccessAwareRouter
   */
  protected $router;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->route = new Route('test');
    $this->accessManager = $this->getMock('Drupal\Core\Access\AccessManagerInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountInterface');
  }

  /**
   * Sets up a chain router with matchRequest.
   */
  protected function setupRouter() {
    $this->chainRouter = $this->getMockBuilder('Symfony\Cmf\Component\Routing\ChainRouter')
      ->disableOriginalConstructor()
      ->getMock();
    $this->chainRouter->expects($this->once())
      ->method('matchRequest')
      ->will($this->returnValue(array(RouteObjectInterface::ROUTE_OBJECT => $this->route)));
    $this->router = new AccessAwareRouter($this->chainRouter, $this->accessManager, $this->currentUser);
  }

  /**
   * Tests the matchRequest() function for access allowed.
   */
  public function testMatchRequestAllowed() {
    $this->setupRouter();
    $request = new Request();
    $access_result = AccessResult::allowed();
    $this->accessManager->expects($this->once())
      ->method('checkRequest')
      ->with($request)
      ->willReturn($access_result);
    $parameters = $this->router->matchRequest($request);
    $expected = [
      RouteObjectInterface::ROUTE_OBJECT => $this->route,
      AccessAwareRouterInterface::ACCESS_RESULT => $access_result,
    ];
    $this->assertSame($expected, $request->attributes->all());
    $this->assertSame($expected, $parameters);
  }

  /**
   * Tests the matchRequest() function for access denied.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testMatchRequestDenied() {
    $this->setupRouter();
    $request = new Request();
    $access_result = AccessResult::forbidden();
    $this->accessManager->expects($this->once())
      ->method('checkRequest')
      ->with($request)
      ->willReturn($access_result);
    $parameters = $this->router->matchRequest($request);
    $expected = [
      AccessAwareRouterInterface::ACCESS_RESULT => $access_result,
    ];
    $this->assertSame($expected, $request->attributes->all());
    $this->assertSame($expected, $parameters);
  }

  /**
   * Ensure that methods are passed to the wrapped router.
   *
   * @covers ::__call
   */
  public function testCall() {
    $mock_router = $this->getMock('Symfony\Component\Routing\RouterInterface');

    $this->chainRouter = $this->getMockBuilder('Symfony\Cmf\Component\Routing\ChainRouter')
      ->disableOriginalConstructor()
      ->setMethods(['add'])
      ->getMock();
    $this->chainRouter->expects($this->once())
      ->method('add')
      ->with($mock_router)
      ->willReturnSelf();
    $this->router = new AccessAwareRouter($this->chainRouter, $this->accessManager, $this->currentUser);

    $this->router->add($mock_router);
  }

}
