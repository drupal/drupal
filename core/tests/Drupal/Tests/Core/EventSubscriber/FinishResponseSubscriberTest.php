<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\EventSubscriber\FinishResponseSubscriber;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\EventSubscriber\FinishResponseSubscriber.
 */
#[CoversClass(FinishResponseSubscriber::class)]
#[Group('EventSubscriber')]
class FinishResponseSubscriberTest extends UnitTestCase {

  /**
   * The mock Kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $kernel;

  /**
   * The mock language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
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

  /**
   * The mock time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->kernel = $this->createMock(HttpKernelInterface::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->requestPolicy = $this->createMock(RequestPolicyInterface::class);
    $this->responsePolicy = $this->createMock(ResponsePolicyInterface::class);
    $this->cacheContextsManager = $this->createMock(CacheContextsManager::class);
    $this->time = $this->createMock(TimeInterface::class);
  }

  /**
   * Finish subscriber should set some default header values.
   *
   * @legacy-covers ::onRespond
   */
  public function testDefaultHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $finishSubscriber->onRespond($event);

    $this->assertEquals(['en'], $response->headers->all('Content-language'));
    $this->assertEquals(['nosniff'], $response->headers->all('X-Content-Type-Options'));
    $this->assertEquals(['SAMEORIGIN'], $response->headers->all('X-Frame-Options'));
  }

  /**
   * Finish subscriber should not overwrite existing header values.
   *
   * @legacy-covers ::onRespond
   */
  public function testExistingHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      FALSE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $response->headers = new ResponseHeaderBag();
    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

    $response->headers->set('X-Content-Type-Options', 'foo');
    $response->headers->set('X-Frame-Options', 'DENY');

    $finishSubscriber->onRespond($event);

    $this->assertEquals(['en'], $response->headers->all('Content-language'));
    // 'X-Content-Type-Options' will be unconditionally set by core.
    $this->assertEquals(['nosniff'], $response->headers->all('X-Content-Type-Options'));
    $this->assertEquals(['DENY'], $response->headers->all('X-Frame-Options'));
  }

  /**
   * Finish subscriber outputs tags, context, max-age if debug is on.
   */
  public function testDebugHeaders(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      TRUE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $this->cacheContextsManager->method('optimizeTokens')
      ->willReturn(['context1', 'context2']);

    $request = $this->createStub(Request::class);
    $response = $this->createStub(CacheableResponse::class);
    $response->headers = new ResponseHeaderBag();

    // Set cache tags, context, max-age.
    $cacheData = (new CacheableMetadata())
      ->setCacheTags(['tag1', 'tag2'])
      ->setCacheContexts(['context1', 'context2'])
      ->setCacheMaxAge(123);
    $response->method('getCacheableMetadata')
      ->willReturn($cacheData);

    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $finishSubscriber->onRespond($event);

    // Check that X-Drupal-Cache-Tags is in the response header.
    $this->assertSame(['tag1 tag2'], $response->headers->all('X-Drupal-Cache-Tags'));
    $this->assertSame(['context1 context2'], $response->headers->all('X-Drupal-Cache-Contexts'));
    $this->assertSame(['123'], $response->headers->all('X-Drupal-Cache-Max-Age'));
  }

  /**
   * Tests that long tags and contexts headers are split into multiple lines.
   */
  public function testDebugCacheTagAndCacheContextsHeadersLength(): void {
    $finishSubscriber = new FinishResponseSubscriber(
      $this->languageManager,
      $this->getConfigFactoryStub(),
      $this->requestPolicy,
      $this->responsePolicy,
      $this->cacheContextsManager,
      $this->time,
      TRUE
    );

    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    $request = $this->createStub(Request::class);
    $response = $this->createStub(CacheableResponse::class);
    $response->headers = new ResponseHeaderBag();

    // Create multiple cache tags that add up to more than 8k bytes. Each tag is
    // 15 bytes. The tags imploded together will have a space between
    // each value, so the total length is 8015.
    for ($i = 0; $i < 501; $i++) {
      $tags[] = 'cache-tag:' . str_pad("$i", 5, '0', STR_PAD_LEFT);
    }

    // For contexts, create multiple values that add up to more than 16k. Each
    // context is 19 bytes. The contexts imploded together will have a space
    // between each value, so the total length is 16019.
    for ($i = 0; $i < 801; $i++) {
      $contexts[] = 'cache-context:' . str_pad("$i", 5, '0', STR_PAD_LEFT);
    }

    $this->cacheContextsManager->method('optimizeTokens')
      ->willReturn($contexts);

    $cacheData = (new CacheableMetadata())
      ->setCacheTags($tags);

    $response->method('getCacheableMetadata')
      ->willReturn($cacheData);

    $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
    $finishSubscriber->onRespond($event);

    // Check that X-Drupal-Cache-Tags has been split into two lines.
    $headers = (string) $response->headers;
    $this->assertEquals(2, substr_count($headers, 'X-Drupal-Cache-Tags: '));
    // Check that X-Drupal-Cache-Contexts has been split into three lines.
    $this->assertEquals(3, substr_count($headers, 'X-Drupal-Cache-Contexts: '));
  }

}
