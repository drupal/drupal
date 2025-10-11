<?php

declare(strict_types=1);

namespace Drupal\Tests\page_cache\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\page_cache\StackMiddleware\PageCache;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests \Drupal\page_cache\StackMiddleware\PageCache.
 */
#[CoversClass(PageCache::class)]
#[Group('page_cache')]
#[IgnoreDeprecations]
class PageCacheLegacyTest extends UnitTestCase {

  /**
   * Tests that page cache is constructed with a http kernel argument.
   */
  public function testDeprecatedKernelArgument(): void {
    $kernel = $this->createStub(HttpKernelInterface::class);
    $cache = $this->createStub(CacheBackendInterface::class);
    $requestPolicy = $this->createStub(RequestPolicyInterface::class);
    $responsePolicy = $this->createStub(ResponsePolicyInterface::class);

    $this->expectDeprecation('Calling Drupal\page_cache\StackMiddleware\PageCache::__construct() without a service closure $http_kernel argument is deprecated in drupal:11.3.0 and it will throw an error in drupal:12.0.0. See https://www.drupal.org/node/3538740');
    new PageCache($kernel, $cache, $requestPolicy, $responsePolicy);
  }

}
