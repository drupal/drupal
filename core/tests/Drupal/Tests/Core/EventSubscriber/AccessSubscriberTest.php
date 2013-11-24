<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\AccessSubscriberTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\EventSubscriber\AccessSubscriber;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;


/**
 * Tests the AccessSubscriber class.
 *
 * @see \Drupal\Core\EventSubscriber\AccessSubscriber
 *
 * @group System
 * @group Drupal
 */
class AccessSubscriberTest extends UnitTestCase {

  /**
   * @var Symfony\Component\HttpKernel\Event\GetResponseEvent|PHPUnit_Framework_MockObject_MockObject
   */
  protected $event;

  /**
   * @var Symfony\Component\HttpFoundation\Request|PHPUnit_Framework_MockObject_MockObject
   */
  protected $request;

  /**
   * @var Symfony\Component\HttpFoundation\ParameterBag|PHPUnit_Framework_MockObject_MockObject
   */
  protected $parameterBag;

  /**
   * @var Symfony\Component\Routing\Route|PHPUnit_Framework_MockObject_MockObject
   */
  protected $route;

  /**
   * @var Drupal\Core\Access\AccessManager|PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * @var Drupal\Core\Session\AccountInterface|PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Access subscriber',
      'description' => 'Tests the access subscriber',
      'group' => 'System',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
      ->disableOriginalConstructor()
      ->getMock();

    $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();

    $this->parameterBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
      ->disableOriginalConstructor()
      ->getMock();

    $this->route = $this->getMockBuilder('Symfony\Component\Routing\Route')
      ->disableOriginalConstructor()
      ->getMock();

    $this->request->attributes = $this->parameterBag;

    $this->event->expects($this->any())
      ->method('getRequest')
      ->will($this->returnValue($this->request));

    $this->accessManager = $this->getMockBuilder('Drupal\Core\Access\AccessManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->currentUser = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests access denied throws a Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException exception.
   *
   * @expectedException Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testAccessSubscriberThrowsAccessDeniedException() {

    $this->parameterBag->expects($this->any())
      ->method('has')
      ->with(RouteObjectInterface::ROUTE_OBJECT)
      ->will($this->returnValue(TRUE));

    $this->parameterBag->expects($this->any())
      ->method('get')
      ->with(RouteObjectInterface::ROUTE_OBJECT)
      ->will($this->returnValue($this->route));

    $this->accessManager->expects($this->any())
      ->method('check')
      ->with($this->anything())
      ->will($this->returnValue(FALSE));

    $subscriber = new AccessSubscriber($this->accessManager, $this->currentUser);
    $subscriber->onKernelRequestAccessCheck($this->event);
  }

  /**
   * Tests that the AccessSubscriber only acts on requests with route object.
   */
  public function testAccessSubscriberOnlyChecksForRequestsWithRouteObject() {
    $this->parameterBag->expects($this->any())
      ->method('has')
      ->with(RouteObjectInterface::ROUTE_OBJECT)
      ->will($this->returnValue(FALSE));

    $this->accessManager->expects($this->never())->method('check');

    $subscriber = new AccessSubscriber($this->accessManager, $this->currentUser);
    $subscriber->onKernelRequestAccessCheck($this->event);
  }

  /**
   * Tests that if access is granted, AccessSubscriber will not throw a Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException exception.
   */
  public function testAccessSubscriberDoesNotAlterRequestIfAccessManagerGrantsAccess() {
    $this->parameterBag->expects($this->any())
      ->method('has')
      ->with(RouteObjectInterface::ROUTE_OBJECT)
      ->will($this->returnValue(TRUE));

    $this->parameterBag->expects($this->any())
      ->method('get')
      ->with(RouteObjectInterface::ROUTE_OBJECT)
      ->will($this->returnValue($this->route));

    $this->accessManager->expects($this->any())
      ->method('check')
      ->with($this->anything())
      ->will($this->returnValue(TRUE));

    $subscriber = new AccessSubscriber($this->accessManager, $this->currentUser);
    $subscriber->onKernelRequestAccessCheck($this->event);
  }

}
