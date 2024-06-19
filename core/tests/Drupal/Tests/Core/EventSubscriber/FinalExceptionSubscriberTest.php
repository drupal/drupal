<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\FinalExceptionSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\FinalExceptionSubscriber
 * @group EventSubscriber
 */
class FinalExceptionSubscriberTest extends UnitTestCase {

  /**
   * @covers ::onException
   */
  public function testOnExceptionWithUnknownFormat(): void {
    $config_factory = $this->getConfigFactoryStub();

    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/test');
    // \Drupal\Core\StackMiddleware\NegotiationMiddleware normally takes care
    // of this so we'll hard code it here.
    $request->setRequestFormat('bananas');
    $e = new MethodNotAllowedHttpException(['POST', 'PUT'], 'test message');
    $event = new ExceptionEvent($kernel->reveal(), $request, HttpKernelInterface::MAIN_REQUEST, $e);
    $subscriber = new TestDefaultExceptionSubscriber($config_factory);
    $subscriber->setStringTranslation($this->getStringTranslationStub());
    $subscriber->onException($event);
    $response = $event->getResponse();

    $this->assertInstanceOf(Response::class, $response);
    $this->assertStringStartsWith('The website encountered an unexpected error. Try again later.<br><br><em class="placeholder">Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException</em>: test message in ', $response->getContent());
    $this->assertEquals(405, $response->getStatusCode());
    $this->assertEquals('POST, PUT', $response->headers->get('Allow'));
    // Also check that the text/plain content type was added.
    $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
  }

}

class TestDefaultExceptionSubscriber extends FinalExceptionSubscriber {

  protected function isErrorDisplayable($error) {
    return TRUE;
  }

  protected function simplifyFileInError($error) {
    return $error;
  }

  protected function isErrorLevelVerbose() {
    return TRUE;
  }

}
