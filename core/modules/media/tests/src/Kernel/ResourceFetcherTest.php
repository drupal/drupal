<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\media\OEmbed\ResourceFetcher;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\ResourceFetcher
 *
 * @group media
 */
class ResourceFetcherTest extends KernelTestBase {

  /**
   * @covers ::__construct
   *
   * @group legacy
   */
  public function testDeprecations(): void {
    $this->expectDeprecation('Passing NULL as the $cache_backend parameter to Drupal\media\OEmbed\ResourceFetcher::__construct() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3223594');
    new ResourceFetcher(
      $this->container->get('http_client'),
      $this->createMock('\Drupal\media\OEmbed\ProviderRepositoryInterface')
    );
  }

}
