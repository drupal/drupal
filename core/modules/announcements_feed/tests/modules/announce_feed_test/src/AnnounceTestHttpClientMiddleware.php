<?php

declare(strict_types=1);

namespace Drupal\announce_feed_test;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * Overrides the requested endpoint when running tests.
 */
class AnnounceTestHttpClientMiddleware {

  /**
   * HTTP middleware that replaces request endpoint for a test one.
   */
  public function __invoke(): \Closure {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
        $test_end_point = \Drupal::state()->get('announce_test_endpoint');
        if ($test_end_point && str_contains((string) $request->getUri(), '://www.drupal.org/announcements.json')) {
          // Only override $uri if it matches the advisories JSON feed to avoid
          // changing any other uses of the 'http_client' service during tests
          // with this module installed.
          $request = $request->withUri(new Uri($test_end_point));
        }
        return $handler($request, $options);
      };
    };
  }

  /**
   * Sets the test endpoint for the advisories JSON feed.
   *
   * @param string $test_endpoint
   *   The test endpoint.
   */
  public static function setAnnounceTestEndpoint(string $test_endpoint): void {
    // Convert the endpoint to an absolute URL.
    $test_endpoint = Url::fromUri('base:/' . $test_endpoint)->setAbsolute()->toString();
    \Drupal::state()->set('announce_test_endpoint', $test_endpoint);
    \Drupal::service('keyvalue.expirable')->get('announcements_feed')->delete('announcements');
    Cache::invalidateTags(['announcements_feed:feed']);
  }

}
