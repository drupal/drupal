<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\aggregator\parser\DefaultParser.
 */

namespace Drupal\aggregator\Plugin\aggregator\parser;

use Drupal\aggregator\Plugin\ParserInterface;
use Drupal\aggregator\FeedInterface;
use Zend\Feed\Reader\Reader;
use Zend\Feed\Reader\Exception\ExceptionInterface;

/**
 * Defines a default parser implementation.
 *
 * Parses RSS, Atom and RDF feeds.
 *
 * @AggregatorParser(
 *   id = "aggregator",
 *   title = @Translation("Default parser"),
 *   description = @Translation("Default parser for RSS, Atom and RDF feeds.")
 * )
 */
class DefaultParser implements ParserInterface {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed) {
    // Set our bridge extension manager to Zend Feed.
    Reader::setExtensionManager(\Drupal::service('feed.bridge.reader'));
    try {
      $channel = Reader::importString($feed->source_string);
    }
    catch (ExceptionInterface $e) {
      watchdog_exception('aggregator', $e);
      drupal_set_message(t('The feed from %site seems to be broken because of error "%error".', array('%site' => $feed->label(), '%error' => $e->getMessage())), 'error');

      return FALSE;
    }

    $feed->setWebsiteUrl($channel->getLink());
    $feed->setDescription($channel->getDescription());
    if ($image = $channel->getImage()) {
      $feed->setImage($image['uri']);
    }
    // Initialize items array.
    $feed->items = array();
    foreach ($channel as $item) {
      // Reset the parsed item.
      $parsed_item = array();
      // Move the values to an array as expected by processors.
      $parsed_item['title'] = $item->getTitle();
      $parsed_item['guid'] = $item->getId();
      $parsed_item['link'] = $item->getLink();
      $parsed_item['description'] = $item->getDescription();
      $parsed_item['author'] = '';
      if ($author = $item->getAuthor()) {
        $parsed_item['author'] = $author['name'];
      }
      $parsed_item['timestamp'] = '';
      if ($date = $item->getDateModified()) {
        $parsed_item['timestamp'] = $date->getTimestamp();
      }
      // Store on $feed object. This is where processors will look for parsed items.
      $feed->items[] = $parsed_item;
    }

    return TRUE;
  }

}
