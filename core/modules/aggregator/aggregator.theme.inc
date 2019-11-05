<?php

/**
 * @file
 * Preprocessors and theme functions of Aggregator module.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Element;

/**
 * Prepares variables for aggregator item templates.
 *
 * Default template: aggregator-item.html.twig.
 *
 * By default this function performs special preprocessing to create a separate
 * variable for the title base field. This preprocessing is skipped if:
 * - a module makes the field's display configurable via the field UI by means
 *   of BaseFieldDefinition::setDisplayConfigurable()
 * - AND the additional entity type property
 *   'enable_base_field_custom_preprocess_skipping' has been set using
 *   hook_entity_type_build().
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

  $variables['url'] = UrlHelper::stripDangerousProtocols($item->getLink());

  // Make title field available separately.  Skip this custom preprocessing if
  // the field display is configurable and skipping has been enabled.
  // @todo https://www.drupal.org/project/drupal/issues/3015623
  //   Eventually delete this code and matching template lines. Using
  //   $variables['content'] is more flexible and consistent.
  $skip_custom_preprocessing = $item->getEntityType()->get('enable_base_field_custom_preprocess_skipping');
  if (!$skip_custom_preprocessing || !$item->getFieldDefinition('title')->isDisplayConfigurable('view')) {
    $variables['title'] = $item->label();
  }
}

/**
 * Prepares variables for aggregator feed templates.
 *
 * Default template: aggregator-feed.html.twig.
 *
 * By default this function performs special preprocessing to create a separate
 * variable for the title base field. This preprocessing is skipped if:
 * - a module makes the field's display configurable via the field UI by means
 *   of BaseFieldDefinition::setDisplayConfigurable()
 * - AND the additional entity type property
 *   'enable_base_field_custom_preprocess_skipping' has been set using
 *   hook_entity_type_build().
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

  // Make title field available separately.  Skip this custom preprocessing if
  // the field display is configurable and skipping has been enabled.
  // @todo https://www.drupal.org/project/drupal/issues/3015623
  //   Eventually delete this code and matching template lines. Using
  //   $variables['content'] is more flexible and consistent.
  $skip_custom_preprocessing = $feed->getEntityType()->get('enable_base_field_custom_preprocess_skipping');
  if (!$skip_custom_preprocessing || !$feed->getFieldDefinition('title')->isDisplayConfigurable('view')) {
    $variables['title'] = $feed->label();
  }
}
