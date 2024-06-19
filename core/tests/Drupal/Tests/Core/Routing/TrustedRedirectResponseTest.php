<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @coversDefaultClass \Drupal\Core\Routing\TrustedRedirectResponse
 * @group Routing
 */
class TrustedRedirectResponseTest extends UnitTestCase {

  /**
   * @covers ::setTargetUrl
   */
  public function testSetTargetUrlWithInternalUrl(): void {
    $redirect_response = new TrustedRedirectResponse('/example');
    $redirect_response->setTargetUrl('/example2');

    $this->assertEquals('/example2', $redirect_response->getTargetUrl());
  }

  /**
   * @covers ::setTargetUrl
   */
  public function testSetTargetUrlWithUntrustedUrl(): void {
    $request_context = new RequestContext();
    $request_context->setCompleteBaseUrl('https://www.drupal.org');
    $container = new ContainerBuilder();
    $container->set('router.request_context', $request_context);
    \Drupal::setContainer($container);

    $redirect_response = new TrustedRedirectResponse('/example');

    $this->expectException(\InvalidArgumentException::class);
    $redirect_response->setTargetUrl('http://evil-url.com/example');
  }

  /**
   * @covers ::setTargetUrl
   */
  public function testSetTargetUrlWithTrustedUrl(): void {
    $redirect_response = new TrustedRedirectResponse('/example');

    $redirect_response->setTrustedTargetUrl('http://good-external-url.com/example');
    $this->assertEquals('http://good-external-url.com/example', $redirect_response->getTargetUrl());
  }

  /**
   * @covers ::createFromRedirectResponse
   * @dataProvider providerCreateFromRedirectResponse
   */
  public function testCreateFromRedirectResponse($redirect_response): void {
    $trusted_redirect_response = TrustedRedirectResponse::createFromRedirectResponse($redirect_response);

    // The trusted redirect response is always a CacheableResponseInterface instance.
    $this->assertInstanceOf(CacheableResponseInterface::class, $trusted_redirect_response);

    // But it is only actually cacheable (non-zero max-age) if the redirect
    // response passed to TrustedRedirectResponse::createFromRedirectResponse()
    // is itself cacheable.
    $expected_cacheability = ($redirect_response instanceof CacheableResponseInterface) ? $redirect_response->getCacheableMetadata() : (new CacheableMetadata())->setCacheMaxAge(0);
    $this->assertEquals($expected_cacheability, $trusted_redirect_response->getCacheableMetadata());
  }

  /**
   * @return array
   */
  public static function providerCreateFromRedirectResponse() {
    return [
      'cacheable-with-tags' => [(new CacheableRedirectResponse('/example'))->addCacheableDependency((new CacheableMetadata())->addCacheTags(['foo']))],
      'cacheable-with-max-age-0' => [(new CacheableRedirectResponse('/example'))->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0))],
      'uncacheable' => [new RedirectResponse('/example')],
    ];
  }

}
