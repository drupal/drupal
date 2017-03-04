<?php

/**
 * @file
 * Provides page callbacks for custom blocks.
 */

use Drupal\Core\Url;

/**
 * Prepares variables for a custom block type creation list templates.
 *
 * Default template: block-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of block types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_block_content_add_list(&$variables) {
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = [
      'link' => \Drupal::l($type->label(), new Url('block_content.add_form', ['block_content_type' => $type->id()], ['query' => $query])),
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
