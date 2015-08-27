<?php

/**
 * @file
 * Preprocessors and theme functions of Aggregator module.
 */

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
  $variables['title'] = $item->label();
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
  $variables['title'] = $feed->label();
}
