<?php

/**
 * @file
 * Provides page callbacks for custom blocks.
 */

use Drupal\custom_block\Entity\CustomBlockType;
use Drupal\custom_block\Entity\CustomBlock;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Prepares variables for a custom block type creation list templates.
 *
 * Default template: custom-block-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of block types.
 *
 * @see custom_block_add_page()
 */
function template_preprocess_custom_block_add_list(&$variables) {
  $variables['types'] = array();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id] = array();
    $query = array();
    if (($destination = drupal_get_destination()) && $destination['destination'] !== current_path()) {
      // A destination parameter is set other than the current path so we
      // respect that by adding it to the generated links. If the current path
      // is returned, we ignore it as we don't want to end up back at block/add.
      $query = $destination;
    }
    $variables['types'][$type->id]['link'] = l($type->label(), 'block/add/' . $type->id(), array('query' => $query));
    $variables['types'][$type->id]['description'] = filter_xss_admin($type->description);
  }
}
