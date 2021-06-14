<?php

namespace Drupal\advisory_feed_test;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a decorator service for the 'http_client' service for testing.
 */
class AdvisoriesTestHttpClient extends Client {

  /**
   * The decorated http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $innerClient;

  /**
   * Constructs an AdvisoriesTestHttpClient object.
   *
   * @param \GuzzleHttp\Client $client
   *   The decorated http_client service.
   */
  public function __construct(Client $client) {
    $this->innerClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function get($uri, array $options = []): ResponseInterface {
    $test_end_point = \Drupal::state()->get('advisories_test_endpoint');
    if ($test_end_point && strpos($uri, '://updates.drupal.org/psa.json') !== FALSE) {
      // Only override $uri if it matches the advisories JSON feed to avoid
      // changing any other uses of the 'http_client' service during tests with
      // this module installed.
      $uri = $test_end_point;
    }
    return $this->innerClient->get($uri, $options);
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
