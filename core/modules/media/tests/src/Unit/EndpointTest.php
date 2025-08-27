<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Unit;

use Drupal\media\OEmbed\Endpoint;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\media\OEmbed\Endpoint.
 */
#[CoversClass(Endpoint::class)]
#[Group('media')]
class EndpointTest extends UnitTestCase {

  /**
   * Tests match url.
   *
   * @legacy-covers ::matchUrl
   */
  public function testMatchUrl(): void {
    $endpoint = new Endpoint(
      'https://www.youtube.com/oembed',
      $this->createMock('\Drupal\media\OEmbed\Provider'),
      ['https://*.youtube.com/playlist?list=*']
    );
    $this->assertTrue($endpoint->matchUrl('https://www.youtube.com/playlist?list=aBc-EzAs123'));
  }

  /**
   * Tests case sensitive match.
   *
   * @legacy-covers ::matchUrl
   */
  public function testCaseSensitiveMatch(): void {
    $endpoint = new Endpoint(
      'https://www.example.com/oembed',
      $this->createMock('\Drupal\media\OEmbed\Provider'),
      ['https://*.example.com/Video/*'],
    );
    $this->assertTrue($endpoint->matchUrl('https://foo.example.com/Video/bar'));
  }

}
