<?php

/**
 * @file
 * Contains \Drupal\aggregator_test\Plugin\aggregator\fetcher\TestFetcher.
 */

namespace Drupal\aggregator_test\Plugin\aggregator\fetcher;

use Drupal\aggregator\Plugin\FetcherInterface;
use Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher;
use Drupal\aggregator\FeedInterface;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Defines a test fetcher implementation.
 *
 * Uses http_default_client class to download the feed.
 *
 * @AggregatorFetcher(
 *   id = "aggregator_test_fetcher",
 *   title = @Translation("Test fetcher"),
 *   description = @Translation("Dummy fetcher for testing purposes.")
 * )
 */
class TestFetcher extends DefaultFetcher implements FetcherInterface {

  /**
   * Implements \Drupal\aggregator\Plugin\FetcherInterface::fetch().
   */
  public function fetch(FeedInterface $feed) {
    if ($feed->label() == 'Do not fetch') {
      return FALSE;
    }
    return parent::fetch($feed);
  }
}
