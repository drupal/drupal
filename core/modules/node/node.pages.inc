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

/**
 * Page callback: Generates an overview table of older revisions of a node.
 *
 * @param object $node
 *   A node object.
 *
 * @return array
 *   An array as expected by drupal_render().
 *
 * @see node_menu()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\node\Controller\NodeController::revisionOverview().
 */
function node_revision_overview($node) {
  $build['#title'] = t('Revisions for %title', array('%title' => $node->label()));

  $header = array(t('Revision'), t('Operations'));

  $rows = array();
  $type = $node->getType();

  $revert_permission = FALSE;
  if ((user_access("revert $type revisions") || user_access('revert all revisions') || user_access('administer nodes')) && $node->access('update')) {
    $revert_permission = TRUE;
  }
  $delete_permission = FALSE;
  if ((user_access("delete $type revisions") || user_access('delete all revisions') || user_access('administer nodes')) && $node->access('delete')) {
    $delete_permission = TRUE;
  }
  $vids = \Drupal::entityManager()->getStorage('node')->revisionIds($node);
  foreach (array_reverse($vids) as $vid) {
    if ($revision = node_revision_load($vid)) {

      $row = array();
      if ($vid == $node->getRevisionId()) {
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->getOwner(),
        );
        $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->getRevisionCreationTime(), 'short'), 'node/' . $node->id()), '!username' => drupal_render($username)))
                                 . (($revision->log->value != '') ? '<p class="revision-log">' . Xss::filter($revision->log->value) . '</p>' : ''),
                       'class' => array('revision-current'));
        $row[] = array('data' => drupal_placeholder(t('current revision')), 'class' => array('revision-current'));
      }
      else {
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->getOwner(),
        );
        $row[] = t('!date by !username', array('!date' => l(format_date($revision->getRevisionCreationTime(), 'short'), "node/" . $node->id() . "/revisions/" . $vid . "/view"), '!username' => drupal_render($username)))
                 . (($revision->log->value != '') ? '<p class="revision-log">' . Xss::filter($revision->log->value) . '</p>' : '');
        if ($revert_permission) {
          $links['revert'] = array(
            'title' => t('Revert'),
            'href' => "node/" . $node->id() . "/revisions/" . $vid . "/revert",
          );
        }
        if ($delete_permission) {
          $links['delete'] = array(
            'title' => t('Delete'),
            'href' => "node/" . $node->id() . "/revisions/" . $vid . "/delete",
          );
        }
        $row[] = array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $links,
          ),
        );
      }
      $rows[] = $row;
    }
  }

  $build['node_revisions_table'] = array(
    '#type' => 'table',
    '#rows' => $rows,
    '#header' => $header,
    '#attached' => array (
      'css' => array(drupal_get_path('module', 'node') . '/css/node.admin.css'),
    ),
  );

  return $build;
}
