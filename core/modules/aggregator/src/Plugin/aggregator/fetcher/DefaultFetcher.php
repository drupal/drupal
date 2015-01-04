<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher.
 */

namespace Drupal\aggregator\Plugin\aggregator\fetcher;

use Drupal\aggregator\Plugin\FetcherInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a default fetcher implementation.
 *
 * Uses the http_client service to download the feed.
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
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a DefaultFetcher object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ClientInterface $http_client, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory')->get('aggregator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    $request = $this->httpClient->createRequest('GET', $feed->getUrl());
    $feed->source_string = FALSE;

    // Generate conditional GET headers.
    if ($feed->getEtag()) {
      $request->addHeader('If-None-Match', $feed->getEtag());
    }
    if ($feed->getLastModified()) {
      $request->addHeader('If-Modified-Since', gmdate(DateTimePlus::RFC7231, $feed->getLastModified()));
    }

    try {
      $response = $this->httpClient->send($request);

      // In case of a 304 Not Modified, there is no new content, so return
      // FALSE.
      if ($response->getStatusCode() == 304) {
        return FALSE;
      }

      $feed->source_string = (string) $response->getBody();
      $feed->setEtag($response->getHeader('ETag'));
      $feed->setLastModified(strtotime($response->getHeader('Last-Modified')));
      $feed->http_headers = $response->getHeaders();

      // Update the feed URL in case of a 301 redirect.

      if ($response->getEffectiveUrl() != $feed->getUrl()) {
        $feed->setUrl($response->getEffectiveUrl());
      }
      return TRUE;
    }
    catch (RequestException $e) {
      $this->logger->warning('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage()));
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage())) , 'warning');
      return FALSE;
    }
  }
}
