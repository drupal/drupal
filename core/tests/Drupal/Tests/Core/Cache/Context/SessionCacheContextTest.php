<?php

namespace Drupal\Tests\Core\Cache\Context;

use Drupal\Core\Cache\Context\SessionCacheContext;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Cache\Context\SessionCacheContext
 * @group Cache
 */
class SessionCacheContextTest extends UnitTestCase {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The session object.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $session;

  public function setUp(): void {
    $this->request = new Request();

    $this->requestStack = new RequestStack();
    $this->requestStack->push($this->request);

    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\SessionInterface')
      ->getMock();
  }

  /**
   * @covers ::getContext
   */
  public function testSameContextForSameSession() {
    $this->request->setSession($this->session);
    $cache_context = new SessionCacheContext($this->requestStack);

    $session_id = 'aSebeZ52bbM6SvADurQP89SFnEpxY6j8';
    $this->session->expects($this->exactly(2))
      ->method('getId')
      ->will($this->returnValue($session_id));

    $context1 = $cache_context->getContext();
    $context2 = $cache_context->getContext();
    $this->assertSame($context1, $context2);
    $this->assertStringNotContainsString($session_id, $context1, 'Session ID not contained in cache context');
  }

  /**
   * @covers ::getContext
   */
  public function testDifferentContextForDifferentSession() {
    $this->request->setSession($this->session);
    $cache_context = new SessionCacheContext($this->requestStack);

    // cspell:disable-next-line
    $session1_id = 'pjH_8aSoofyCDQiuVYXJcbfyr-CPtkUY';
    $session2_id = 'aSebeZ52bbM6SvADurQP89SFnEpxY6j8';
    $this->session->expects($this->exactly(2))
      ->method('getId')
      ->willReturnOnConsecutiveCalls($session1_id, $session2_id);

    $context1 = $cache_context->getContext();
    $context2 = $cache_context->getContext();
    $this->assertNotEquals($context1, $context2);

    $this->assertStringNotContainsString($session1_id, $context1, 'Session ID not contained in cache context');
    $this->assertStringNotContainsString($session2_id, $context2, 'Session ID not contained in cache context');
  }

  /**
   * @covers ::getContext
   */
  public function testContextWithoutSessionInRequest() {
    $cache_context = new SessionCacheContext($this->requestStack);

    $this->assertSame('none', $cache_context->getContext());
  }

}
