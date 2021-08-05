<?php

namespace Drupal\Tests\media\Unit;

use Drupal\media\OEmbed\Endpoint;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\Endpoint
 *
 * @group media
 */
class EndpointTest extends UnitTestCase {

  /**
   * @covers ::matchUrl
   */
  public function testMatchUrl(): void {
    $endpoint = new Endpoint(
      'https://www.youtube.com/oembed',
      $this->createMock('\Drupal\media\OEmbed\Provider'),
      ['https://*.youtube.com/playlist?list=*']
    );
    $this->assertTrue($endpoint->matchUrl('https://www.youtube.com/playlist?list=aBc-EzAs123'));
  }

}
