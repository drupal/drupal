<?php

/**
 * @file
 * Provides page callbacks for custom blocks.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\block_content\Entity\BlockContent;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
  $variables['types'] = array();
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $variables['types'][$type->id()] = array(
      'link' => \Drupal::l($type->label(), new Url('block_content.add_form', array('block_content_type' => $type->id()), array('query' => $query))),
      'description' => Xss::filterAdmin($type->getDescription()),
      'title' => $type->label(),
      'localized_options' => array(
        'query' => $query,
      ),
    );
  }
}
