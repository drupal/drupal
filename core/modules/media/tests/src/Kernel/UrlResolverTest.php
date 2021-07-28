<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\OEmbed\UrlResolver;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\UrlResolver
 *
 * @group media
 */
class UrlResolverTest extends KernelTestBase {

  /**
   * @covers ::__construct
   *
   * @group legacy
   */
  public function testDeprecations(): void {
    $this->expectDeprecation('Passing NULL as the $cache_backend parameter to Drupal\media\OEmbed\UrlResolver::__construct() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3223594');
    new UrlResolver(
      $this->createMock('\Drupal\media\OEmbed\ProviderRepositoryInterface'),
      $this->createMock('\Drupal\media\OEmbed\ResourceFetcherInterface'),
      $this->container->get('http_client'),
      $this->container->get('module_handler')
    );
  }

}
