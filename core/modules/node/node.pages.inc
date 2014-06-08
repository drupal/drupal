<?php

/**
 * @file
 * Callbacks for adding, editing, and deleting content and managing revisions.
 *
 * Also includes validation, submission and other helper functions.
 *
 * @see node_menu()
 */

use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\NodeInterface;

/**
 * Prepares variables for list of available node type templates.
 *
 * Default template: node-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of content types.
 *
 * @see node_add_page()
 */
function template_preprocess_node_add_list(&$variables) {
  $variables['types'] = array();
  if (!empty($variables['content'])) {
    foreach ($variables['content'] as $type) {
      $variables['types'][$type->type] = array(
        'type' => $type->type,
        'add_link' => l($type->name, 'node/add/' . $type->type),
        'description' => Xss::filterAdmin($type->description),
      );
    }
  }
}

/**
 * Generates a node preview.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node to preview.
 *
 * @return
 *   An HTML-formatted string of a node preview.
 *
 * @see node_form_build_preview()
 */
function node_preview(NodeInterface $node, array &$form_state) {
  if ($node->access('create') || $node->access('update')) {

    $node->changed = REQUEST_TIME;

    // Display a preview of the node.
    if (!form_get_errors($form_state)) {
      $node->in_preview = TRUE;
      $node_preview = array(
        '#theme' => 'node_preview',
        '#node' => $node,
      );
      $output = drupal_render($node_preview);
      unset($node->in_preview);
    }

    return $output;
  }
}

/**
 * Prepares variables for node preview templates.
 *
 * Default template: node-preview.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - node: The node entity which is being previewed.
 *
 * @see NodeForm::preview()
 * @see node_preview()
 */
function template_preprocess_node_preview(&$variables) {
  $node = $variables['node'];

  // Render trimmed teaser version of the post.
  $node_teaser = node_view($node, 'teaser');
  $node_teaser['#attached']['library'][] = 'node/drupal.node.preview';
  $variables['teaser'] = $node_teaser;
  // Render full version of the post.
  $node_full = node_view($node, 'full');
  $variables['full'] = $node_full;

  // Display a preview of the teaser only if the content of the teaser is
  // different to the full post.
  if ($variables['teaser'] != $variables['full']) {
    drupal_set_message(t('The trimmed version of your post shows what your post looks like when promoted to the main page or when exported for syndication.<span class="no-js"> You can insert the delimiter "&lt;!--break--&gt;" (without the quotes) to fine-tune where your post gets split.</span>'));
    $variables['preview_teaser'] = TRUE;
  }
  else {
    $variables['preview_teaser'] = FALSE;
  }
}
