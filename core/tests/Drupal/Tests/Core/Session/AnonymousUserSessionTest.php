<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Session\AnonymousUserSessionTest.
 */

namespace Drupal\Tests\Core\Session;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\AnonymousUserSession;
  use Drupal\user\RoleInterface;
  use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Session\AnonymousUserSession
 * @group Session
 */
class AnonymousUserSessionTest extends UnitTestCase {

  /**
   * Tests creating an AnonymousUserSession when the request is available.
   *
   * @covers ::__construct
   */
  public function testAnonymousUserSessionWithRequest() {
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $request->expects($this->once())
      ->method('getClientIp')
      ->will($this->returnValue('test'));
    $container = new ContainerBuilder();
    $requestStack = new RequestStack();
    $requestStack->push($request);
    $container->set('request_stack', $requestStack);
    \Drupal::setContainer($container);

    $anonymous_user = new AnonymousUserSession();

    $this->assertSame('test', $anonymous_user->getHostname());
  }

  /**
   * Tests creating an AnonymousUserSession when the request is not available.
   *
   * @covers ::__construct
   */
  public function testAnonymousUserSessionWithNoRequest() {
    $container = new ContainerBuilder();

    \Drupal::setContainer($container);

    $anonymous_user = new AnonymousUserSession();

    $this->assertSame('', $anonymous_user->getHostname());
  }

  /**
   * Tests the method getRoles exclude or include locked roles based in param.
   *
   * @covers ::getRoles
   * @todo Move roles constants to a class/interface
   */
  public function testUserGetRoles() {
    $anonymous_user = new AnonymousUserSession();
    $this->assertEquals(array(RoleInterface::ANONYMOUS_ID), $anonymous_user->getRoles());
    $this->assertEquals(array(), $anonymous_user->getRoles(TRUE));
  }

}
