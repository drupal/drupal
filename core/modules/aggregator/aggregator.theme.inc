<?php

/**
 * @file
 * Preprocessors and theme functions of Aggregator module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Render\Element;

/**
 * Prepares variables for aggregator item templates.
 *
 * Default template: aggregator-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_aggregator_item(&$variables) {
  $item = $variables['elements']['#aggregator_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['url'] = check_url($item->getLink());
  $variables['title'] = String::checkPlain($item->label());
}

/**
 * Prepares variables for aggregator feed templates.
 *
 * Default template: aggregator-feed.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_aggregator_feed(&$variables) {
  $feed = $variables['elements']['#aggregator_feed'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
  $variables['full'] = $variables['elements']['#view_mode'] == 'full';
  $variables['title'] = String::checkPlain($feed->label());
}

/**
 * Prepares variables for individual feed item block templates.
 *
 * Default template: aggregator-block-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: The item to be displayed.
 *   - feed: Not used.
 */
function template_preprocess_aggregator_block_item(&$variables) {
  // Display the external link to the item.
  $item = $variables['item'];
  $variables['url'] = check_url($item->getLink());
  $variables['title'] = String::checkPlain($item->label());
}
