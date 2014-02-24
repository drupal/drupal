<?php

/**
 * @file
 * Contains \Drupal\aggregator_test\Plugin\aggregator\parser\TestParser.
 */

namespace Drupal\aggregator_test\Plugin\aggregator\parser;

use Drupal\aggregator\Plugin\ParserInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\aggregator\Plugin\aggregator\parser\DefaultParser;

/**
 * Defines a Test parser implementation.
 *
 * Parses RSS, Atom and RDF feeds.
 *
 * @AggregatorParser(
 *   id = "aggregator_test_parser",
 *   title = @Translation("Test parser"),
 *   description = @Translation("Dummy parser for testing purposes.")
 * )
 */
class TestParser extends DefaultParser implements ParserInterface {

  /**
   * Implements \Drupal\aggregator\Plugin\ParserInterface::parse().
   *
   * @todo Actually test this.
   */
  public function parse(FeedInterface $feed) {
    return parent::parse($feed);
  }
}
