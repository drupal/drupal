<?php

/**
 * @file
 * Provides page callbacks for custom blocks.
 */

use Drupal\Component\Utility\Xss;
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
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => \Drupal::l($type->label(), 'custom_block.add_form', array('custom_block_type' => $type->id()), array('query' => $query)),
      'description' => Xss::filterAdmin($type->description),
      'title' => $type->label(),
      'localized_options' => array(
        'query' => $query,
      ),
    );
  }
}
