<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Tests Drupal\Core\Routing\TrustedRedirectResponse.
 */
#[CoversClass(TrustedRedirectResponse::class)]
#[Group('Routing')]
class TrustedRedirectResponseTest extends UnitTestCase {

  /**
   * Tests set target url with internal url.
   */
  public function testSetTargetUrlWithInternalUrl(): void {
    $redirect_response = new TrustedRedirectResponse('/example');
    $redirect_response->setTargetUrl('/example2');

    $this->assertEquals('/example2', $redirect_response->getTargetUrl());
  }

  /**
   * Tests set target url with untrusted url.
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
   * Tests set target url with trusted url.
   */
  public function testSetTargetUrlWithTrustedUrl(): void {
    $redirect_response = new TrustedRedirectResponse('/example');

    $redirect_response->setTrustedTargetUrl('http://good-external-url.com/example');
    $this->assertEquals('http://good-external-url.com/example', $redirect_response->getTargetUrl());
  }

  /**
   * Tests create from redirect response.
   */
  #[DataProvider('providerCreateFromRedirectResponse')]
  public function testCreateFromRedirectResponse($redirect_response): void {
    $trusted_redirect_response = TrustedRedirectResponse::createFromRedirectResponse($redirect_response);

    // The trusted redirect response is always a CacheableResponseInterface
    // instance.
    $this->assertInstanceOf(CacheableResponseInterface::class, $trusted_redirect_response);

    // But it is only actually cacheable (non-zero max-age) if the redirect
    // response passed to TrustedRedirectResponse::createFromRedirectResponse()
    // is itself cacheable.
    $expected_cacheability = ($redirect_response instanceof CacheableResponseInterface) ? $redirect_response->getCacheableMetadata() : (new CacheableMetadata())->setCacheMaxAge(0);
    $this->assertEquals($expected_cacheability, $trusted_redirect_response->getCacheableMetadata());
  }

  /**
   * @return array
   *   An array of test cases, each containing a redirect response instance.
   */
  public static function providerCreateFromRedirectResponse(): array {
    return [
      'cacheable-with-tags' => [(new CacheableRedirectResponse('/example'))->addCacheableDependency((new CacheableMetadata())->addCacheTags(['foo']))],
      'cacheable-with-max-age-0' => [(new CacheableRedirectResponse('/example'))->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0))],
      'uncacheable' => [new RedirectResponse('/example')],
    ];
  }

}
