<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher.
 */

namespace Drupal\aggregator\Plugin\aggregator\fetcher;

use Drupal\aggregator\Plugin\FetcherInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a default fetcher implementation.
 *
 * Uses the http_default_client service to download the feed.
 *
 * @AggregatorFetcher(
 *   id = "aggregator",
 *   title = @Translation("Default fetcher"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler.")
 * )
 */
class DefaultFetcher implements FetcherInterface, ContainerFactoryPluginInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a DefaultFetcher object.
   *
   * @param \Guzzle\Http\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $container->get('http_default_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    $request = $this->httpClient->get($feed->getUrl());
    $feed->source_string = FALSE;

    // Generate conditional GET headers.
    if ($feed->getEtag()) {
      $request->addHeader('If-None-Match', $feed->getEtag());
    }
    if ($feed->getLastModified()) {
      $request->addHeader('If-Modified-Since', gmdate(DATE_RFC1123, $feed->getLastModified()));
    }

    try {
      $response = $request->send();

      // In case of a 304 Not Modified, there is no new content, so return
      // FALSE.
      if ($response->getStatusCode() == 304) {
        return FALSE;
      }

      $feed->source_string = $response->getBody(TRUE);
      $feed->setEtag($response->getEtag());
      $feed->setLastModified(strtotime($response->getLastModified()));
      $feed->http_headers = $response->getHeaders();

      // Update the feed URL in case of a 301 redirect.

      if ($response->getEffectiveUrl() != $feed->getUrl()) {
        $feed->setUrl($response->getEffectiveUrl());
      }
      return TRUE;
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      watchdog('aggregator', 'The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())));
      return FALSE;
    }
    catch (RequestException $e) {
      watchdog('aggregator', 'The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage()), WATCHDOG_WARNING);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage())));
      return FALSE;
    }
  }
}
