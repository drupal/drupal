<?php

declare(strict_types=1);

namespace Drupal\advisory_feed_test;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * Overrides the User-Agent HTTP header for outbound HTTP requests.
 */
class AdvisoryTestClientMiddleware {

  /**
   * HTTP middleware that replaces URI for advisory requests.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
        $test_end_point = \Drupal::state()->get('advisories_test_endpoint');
        if ($test_end_point && str_contains((string) $request->getUri(), '://updates.drupal.org/psa.json')) {
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
   * @param bool $delete_stored_response
   *   Whether to delete stored feed response.
   */
  public static function setTestEndpoint(string $test_endpoint, bool $delete_stored_response = FALSE): void {
    \Drupal::state()->set('advisories_test_endpoint', $test_endpoint);
    if ($delete_stored_response) {
      \Drupal::service('system.sa_fetcher')->deleteStoredResponse();
    }
  }

}
