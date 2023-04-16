<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\EventSubscriber\PsrResponseSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\PsrResponseSubscriber
 * @group EventSubscriber
 */
class PsrResponseSubscriberTest extends UnitTestCase {

  /**
   * The tested path root subscriber.
   *
   * @var \Drupal\Core\EventSubscriber\PsrResponseSubscriber
   */
  protected $psrResponseSubscriber;

  /**
   * The tested path root subscriber.
   *
   * @var \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpFoundationFactoryMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $factory = $this->getMockBuilder('Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $factory
      ->expects($this->any())
      ->method('createResponse')
      ->willReturn($this->createMock('Symfony\Component\HttpFoundation\Response'));

    $this->httpFoundationFactoryMock = $factory;

    $this->psrResponseSubscriber = new PsrResponseSubscriber($this->httpFoundationFactoryMock);
  }

  /**
   * Tests altering and finished event.
   *
   * @covers ::onKernelView
   */
  public function testConvertsControllerResult() {
    $event = $this->createEvent($this->createMock('Psr\Http\Message\ResponseInterface'));
    $this->psrResponseSubscriber->onKernelView($event);
    $this->assertInstanceOf(Response::class, $event->getResponse());
  }

  /**
   * Tests altering and finished event.
   *
   * @covers ::onKernelView
   */
  public function testDoesNotConvertControllerResult() {
    $event = $this->createEvent([]);
    $this->psrResponseSubscriber->onKernelView($event);
    $this->assertNull($event->getResponse());

    $event = $this->createEvent(NULL);
    $this->psrResponseSubscriber->onKernelView($event);
    $this->assertNull($event->getResponse());
  }

  /**
   * Sets up an event that returns $controllerResult.
   *
   * @param mixed $controller_result
   *   The return Object.
   *
   * @return \Symfony\Component\HttpKernel\Event\ViewEvent
   *   A ViewEvent object to test.
   */
  protected function createEvent($controller_result) {
    return new ViewEvent(
      $this->createMock(HttpKernelInterface::class),
      $this->createMock(Request::class),
      HttpKernelInterface::MAIN_REQUEST,
      $controller_result
    );
  }

}
