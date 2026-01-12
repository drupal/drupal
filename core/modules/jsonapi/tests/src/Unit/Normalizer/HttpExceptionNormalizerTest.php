<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Unit\Normalizer;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Normalizer\HttpExceptionNormalizer;
use Drupal\jsonapi\Normalizer\Value\HttpExceptionNormalizerValue;
use Drupal\Tests\Core\Render\TestCacheableDependency;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\jsonapi\Normalizer\HttpExceptionNormalizer.
 *
 * @internal
 */
#[CoversClass(HttpExceptionNormalizer::class)]
#[Group('jsonapi')]
class HttpExceptionNormalizerTest extends UnitTestCase {

  /**
   * Tests normalize.
   */
  public function testNormalize(): void {
    $request_stack = $this->prophesize(RequestStack::class);
    $request_stack->getCurrentRequest()->willReturn(Request::create('http://localhost/'));
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('request_stack')->willReturn($request_stack->reveal());
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('error_level')->willReturn(ERROR_REPORTING_DISPLAY_VERBOSE);
    $config_factory = $this->prophesize(ConfigFactory::class);
    $config_factory->get('system.logging')->willReturn($config->reveal());
    $container->get('config.factory')->willReturn($config_factory->reveal());
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(['user.permissions'])->willReturn(TRUE);
    $container->get('cache_contexts_manager')->willReturn($cache_contexts_manager->reveal());
    \Drupal::setContainer($container->reveal());
    $cacheability = new TestCacheableDependency([], [], Cache::PERMANENT);
    $exception = new CacheableAccessDeniedHttpException($cacheability, 'lorem', NULL, 13);
    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->hasPermission('access site reports')->willReturn(TRUE);
    $normalizer = new HttpExceptionNormalizer($current_user->reveal());
    $normalized = $normalizer->normalize($exception, 'api_json');
    $this->assertInstanceOf(HttpExceptionNormalizerValue::class, $normalized);
    $this->assertEquals(0, $normalized->getCacheMaxAge());
    $normalized = $normalized->getNormalization();
    $error = $normalized[0];
    $this->assertNotEmpty($error['meta']);
    $this->assertNotEmpty($error['source']);
    $this->assertSame('13', $error['code']);
    $this->assertSame('403', $error['status']);
    $this->assertEquals('Forbidden', $error['title']);
    $this->assertEquals('lorem', $error['detail']);
    $this->assertArrayHasKey('trace', $error['meta']);
    $this->assertNotEmpty($error['meta']['trace']);

    $current_user = $this->prophesize(AccountInterface::class);
    $current_user->hasPermission('access site reports')->willReturn(FALSE);
    $normalizer = new HttpExceptionNormalizer($current_user->reveal());
    $normalized = $normalizer->normalize($exception, 'api_json');
    $this->assertInstanceOf(HttpExceptionNormalizerValue::class, $normalized);
    $this->assertEquals(Cache::PERMANENT, $normalized->getCacheMaxAge());
    $normalized = $normalized->getNormalization();
    $error = $normalized[0];
    $this->assertArrayNotHasKey('meta', $error);
    $this->assertArrayNotHasKey('source', $error);
  }

}
