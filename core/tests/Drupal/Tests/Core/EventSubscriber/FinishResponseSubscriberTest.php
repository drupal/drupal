<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\EventSubscriber\FinishResponseSubscriber;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\FinishResponseSubscriber
 * @group EventSubscriber
 */
class FinishResponseSubscriberTest extends UnitTestCase {

  /**
   * The mock language manager.
   *
   * @var Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The mock request policy.
   *
   * @var \Drupal\Core\PageCache\RequestPolicyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestPolicy;

  /**
   * The mock response policy.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $responsePolicy;

  /**
   * The mock cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheContextsManager;

  protected function setUp(): void {
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->requestPolicy = $this->createMock(RequestPolicyInterface::class);
    $this->responsePolicy = $this->createMock(ResponsePolicyInterface::class);
    $this->cacheContextsManager = $this->createMock(CacheContextsManager::class);
  }

  /**
   * Finish subscriber should set some default header values.
   *
   * @covers ::onRespond
   */
  public function testDefaultHeaders() {

    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      FALSE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = $this->createMock(ResponseEvent::class);

    $event->method('isMasterRequest')->willReturn(TRUE);
    $event->method('getRequest')
      ->willReturn($request);
    $event->method('getResponse')
      ->willReturn($response);

    $finishSubscriber->onRespond($event);

    $this->assertEquals(['IE=edge'], $response->headers->all('X-UA-Compatible'));
    $this->assertEquals(['nosniff'], $response->headers->all('X-Content-Type-Options'));
    $this->assertEquals(['SAMEORIGIN'], $response->headers->all('X-Frame-Options'));
  }

  /**
   * Finish subscriber should not overwrite existing header values.
   *
   * @covers ::onRespond
   */
  public function testExistingHeaders() {

    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      FALSE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = $this->createMock(ResponseEvent::class);

    $event->method('isMasterRequest')->willReturn(TRUE);
    $event->method('getRequest')
      ->willReturn($request);
    $event->method('getResponse')
      ->willReturn($response);

    $response->headers->set('X-UA-Compatible', 'FOO=bar');
    $response->headers->set('X-Content-Type-Options', 'foo');
    $response->headers->set('X-Frame-Options', 'DENY');

    $finishSubscriber->onRespond($event);

    $this->assertEquals(['FOO=bar'], $response->headers->all('X-UA-Compatible'));
    $this->assertEquals(['foo'], $response->headers->all('X-Content-Type-Options'));
    $this->assertEquals(['DENY'], $response->headers->all('X-Frame-Options'));
  }

}
