<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\PsrResponseSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\EventSubscriber\PsrResponseSubscriber.
 */
#[CoversClass(PsrResponseSubscriber::class)]
#[Group('EventSubscriber')]
class PsrResponseSubscriberTest extends UnitTestCase {

  /**
   * The tested path root subscriber.
   *
   * @var \Drupal\Core\EventSubscriber\PsrResponseSubscriber
   */
  protected $psrResponseSubscriber;

  /**
   * The tested path root subscriber.
   */
  protected HttpFoundationFactoryInterface&Stub $httpFoundationFactoryMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $factory = $this->createStub(HttpFoundationFactoryInterface::class);
    $factory
      ->method('createResponse')
      ->willReturn($this->createStub(Response::class));

    $this->httpFoundationFactoryMock = $factory;

    $this->psrResponseSubscriber = new PsrResponseSubscriber($this->httpFoundationFactoryMock);
  }

  /**
   * Tests altering and finished event.
   *
   * @legacy-covers ::onKernelView
   */
  public function testConvertsControllerResult(): void {
    $body = $this->createStub(StreamInterface::class);
    $body->method('isSeekable')->willReturn(TRUE);
    $psr_response = $this->createStub(ResponseInterface::class);
    $psr_response->method('getBody')->willReturn($body);

    $event = $this->createEvent($psr_response);
    $this->psrResponseSubscriber->onKernelView($event);
    $this->assertInstanceOf(Response::class, $event->getResponse());
  }

  /**
   * Tests that a seekable body results in a non-streamed response.
   *
   * @legacy-covers ::onKernelView
   */
  public function testConvertsSeekableBodyWithoutStreaming(): void {
    $body = $this->createStub(StreamInterface::class);
    $body->method('isSeekable')->willReturn(TRUE);
    $psr_response = $this->createStub(ResponseInterface::class);
    $psr_response->method('getBody')->willReturn($body);

    $factory = $this->createMock(HttpFoundationFactoryInterface::class);
    $factory->expects($this->once())
      ->method('createResponse')
      ->with($psr_response, FALSE)
      ->willReturn($this->createStub(Response::class));

    $subscriber = new PsrResponseSubscriber($factory);
    $event = $this->createEvent($psr_response);
    $subscriber->onKernelView($event);
    $this->assertInstanceOf(Response::class, $event->getResponse());
  }

  /**
   * Tests that a non-seekable body results in a streamed response.
   *
   * @legacy-covers ::onKernelView
   */
  public function testConvertsNonSeekableBodyWithStreaming(): void {
    $body = $this->createStub(StreamInterface::class);
    $body->method('isSeekable')->willReturn(FALSE);
    $psr_response = $this->createStub(ResponseInterface::class);
    $psr_response->method('getBody')->willReturn($body);

    $factory = $this->createMock(HttpFoundationFactoryInterface::class);
    $factory->expects($this->once())
      ->method('createResponse')
      ->with($psr_response, TRUE)
      ->willReturn($this->createStub(StreamedResponse::class));

    $subscriber = new PsrResponseSubscriber($factory);
    $event = $this->createEvent($psr_response);
    $subscriber->onKernelView($event);
    $this->assertInstanceOf(StreamedResponse::class, $event->getResponse());
  }

  /**
   * Tests altering and finished event.
   *
   * @legacy-covers ::onKernelView
   */
  public function testDoesNotConvertControllerResult(): void {
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
  protected function createEvent($controller_result): ViewEvent {
    return new ViewEvent(
      $this->createStub(HttpKernelInterface::class),
      $this->createStub(Request::class),
      HttpKernelInterface::MAIN_REQUEST,
      $controller_result
    );
  }

}
