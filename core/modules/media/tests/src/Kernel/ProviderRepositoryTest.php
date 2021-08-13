<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\NullBackend;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\OEmbed\ProviderRepository;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\ProviderRepository
 *
 * @group media
 */
class ProviderRepositoryTest extends KernelTestBase {

  /**
   * @covers ::__construct
   *
   * @group legacy
   */
  public function testDeprecations(): void {
    // Passing a cache backend in the key-value store's place, and the max age
    // in the logger factory's place, should raise deprecation notices.
    $this->expectDeprecation('The keyvalue service should be passed to Drupal\media\OEmbed\ProviderRepository::__construct() since drupal:9.3.0 and is required in drupal:10.0.0. See https://www.drupal.org/node/3186186');
    $this->expectDeprecation('The logger.factory service should be passed to Drupal\media\OEmbed\ProviderRepository::__construct() since drupal:9.3.0 and is required in drupal:10.0.0. See https://www.drupal.org/node/3186186');
    $providers = new ProviderRepository(
      $this->container->get('http_client'),
      $this->container->get('config.factory'),
      $this->container->get('datetime.time'),
      new NullBackend('test'),
      86400
    );
    $this->expectDeprecation('The property cacheBackend (cache.default service) is deprecated in Drupal\media\OEmbed\ProviderRepository and will be removed before Drupal 10.0.0.');
    $this->assertInstanceOf(CacheBackendInterface::class, $providers->cacheBackend);

    // Ensure that the $max_age was properly set, even though it was passed in
    // the logger factory's position.
    $reflector = new \ReflectionClass($providers);
    $property = $reflector->getProperty('maxAge');
    $property->setAccessible(TRUE);
    $this->assertSame(86400, $property->getValue($providers));
  }

}
