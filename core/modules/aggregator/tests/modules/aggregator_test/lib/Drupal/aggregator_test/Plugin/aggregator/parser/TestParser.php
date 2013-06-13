<?php

/**
 * @file
 * Contains \Drupal\aggregator_test\Plugin\aggregator\parser\TestParser.
 */

namespace Drupal\aggregator_test\Plugin\aggregator\parser;

use Drupal\aggregator\Plugin\ParserInterface;
use Drupal\aggregator\Plugin\Core\Entity\Feed;
use Drupal\aggregator\Plugin\aggregator\parser\DefaultParser;
use Drupal\aggregator\Annotation\AggregatorParser;
use Drupal\Core\Annotation\Translation;

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
  public function parse(Feed $feed) {
    return parent::parse($feed);
  }
}
