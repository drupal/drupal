<?php

/**
 * @file
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for a block type creation list templates.
 *
 * Default template: block-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of block types.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use
 *   entity_add_list instead.
 *
 * @see https://www.drupal.org/node/3530643
 */
function template_preprocess_block_content_add_list(&$variables): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use entity_add_list instead. See https://www.drupal.org/node/3530643', E_USER_DEPRECATED);
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = [
      'link' => Link::fromTextAndUrl($type->label(), Url::fromRoute('block_content.add_form', ['block_content_type' => $type->id()], ['query' => $query]))->toString(),
      'description' => [
        '#markup' => $type->getDescription(),
      ],
      'title' => $type->label(),
      'localized_options' => [
        'query' => $query,
      ],
    ];
  }
}
