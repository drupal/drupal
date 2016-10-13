<?php

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\SessionCacheContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\SessionCacheContext
 * @group Cache
 */
class SessionCacheContextTest extends \PHPUnit_Framework_TestCase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The session object.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $session;

  /**
   * The session cache context.
   *
   * @var \Drupal\Core\Cache\Context\SessionCacheContext
   */
  protected $cacheContext;

  public function setUp() {
    $request = new Request();

    $this->requestStack = new RequestStack();
    $this->requestStack->push($request);

    $this->session = $this->getMock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
    $request->setSession($this->session);

    $this->cacheContext = new SessionCacheContext($this->requestStack);
  }

  /**
   * @covers ::getContext
   */
  public function testSameContextForSameSession() {
    $session_id = 'aSebeZ52bbM6SvADurQP89SFnEpxY6j8';
    $this->session->expects($this->exactly(2))
      ->method('getId')
      ->will($this->returnValue($session_id));

    $context1 = $this->cacheContext->getContext();
    $context2 = $this->cacheContext->getContext();
    $this->assertSame($context1, $context2);
    $this->assertSame(FALSE, strpos($context1, $session_id), 'Session ID not contained in cache context');
  }

  /**
   * @covers ::getContext
   */
  public function testDifferentContextForDifferentSession() {
    $session1_id = 'pjH_8aSoofyCDQiuVYXJcbfyr-CPtkUY';
    $this->session->expects($this->at(0))
      ->method('getId')
      ->will($this->returnValue($session1_id));

    $session2_id = 'aSebeZ52bbM6SvADurQP89SFnEpxY6j8';
    $this->session->expects($this->at(1))
      ->method('getId')
      ->will($this->returnValue($session2_id));

    $context1 = $this->cacheContext->getContext();
    $context2 = $this->cacheContext->getContext();
    $this->assertNotEquals($context1, $context2);

    $this->assertSame(FALSE, strpos($context1, $session1_id), 'Session ID not contained in cache context');
    $this->assertSame(FALSE, strpos($context2, $session2_id), 'Session ID not contained in cache context');
  }

}
