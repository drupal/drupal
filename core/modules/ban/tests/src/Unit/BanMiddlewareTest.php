<?php

namespace Drupal\Tests\ban\Unit;

use Drupal\ban\BanMiddleware;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\ban\BanMiddleware
 * @group ban
 */
class BanMiddlewareTest extends UnitTestCase {

  /**
   * The mocked wrapped kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $kernel;

  /**
   * The mocked ban IP manager.
   *
   * @var \Drupal\ban\BanIpManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $banManager;

  /**
   * The tested ban middleware.
   *
   * @var \Drupal\ban\BanMiddleware
   */
  protected $banMiddleware;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->kernel = $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->banManager = $this->createMock('Drupal\ban\BanIpManagerInterface');
    $this->banMiddleware = new BanMiddleware($this->kernel, $this->banManager);
  }

  /**
   * Tests a banned IP.
   */
  public function testBannedIp() {
    $banned_ip = '17.0.0.2';
    $this->banManager->expects($this->once())
      ->method('isBanned')
      ->with($banned_ip)
      ->willReturn(TRUE);

    $this->kernel->expects($this->never())
      ->method('handle');

    $request = Request::create('/test-path');
    $request->server->set('REMOTE_ADDR', $banned_ip);
    $response = $this->banMiddleware->handle($request);

    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Tests an unbanned IP.
   */
  public function testUnbannedIp() {
    $unbanned_ip = '18.0.0.2';
    $this->banManager->expects($this->once())
      ->method('isBanned')
      ->with($unbanned_ip)
      ->willReturn(FALSE);

    $request = Request::create('/test-path');
    $request->server->set('REMOTE_ADDR', $unbanned_ip);
    $expected_response = new Response(200);
    $this->kernel->expects($this->once())
      ->method('handle')
      ->with($request, HttpKernelInterface::MASTER_REQUEST, TRUE)
      ->willReturn($expected_response);

    $response = $this->banMiddleware->handle($request);

    $this->assertSame($expected_response, $response);
  }

}
