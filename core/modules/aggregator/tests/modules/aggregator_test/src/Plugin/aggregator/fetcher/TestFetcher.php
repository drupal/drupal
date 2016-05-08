<?php

namespace Drupal\aggregator_test\Plugin\aggregator\fetcher;

use Drupal\aggregator\Plugin\FetcherInterface;
use Drupal\aggregator\Plugin\aggregator\fetcher\DefaultFetcher;
use Drupal\aggregator\FeedInterface;

/**
 * Defines a test fetcher implementation.
 *
 * Uses http_client class to download the feed.
 *
 * @AggregatorFetcher(
 *   id = "aggregator_test_fetcher",
 *   title = @Translation("Test fetcher"),
 *   description = @Translation("Dummy fetcher for testing purposes.")
 * )
 */
class TestFetcher extends DefaultFetcher implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed) {
    if ($feed->label() == 'Do not fetch') {
      return FALSE;
    }
    return parent::fetch($feed);
  }

}
