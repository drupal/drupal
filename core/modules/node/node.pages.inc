<?php

/**
 * @file
 * Callbacks for adding, editing, and deleting content and managing revisions.
 *
 * Also includes validation, submission and other helper functions.
 *
 * @see node_menu()
 */

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\NodeInterface;

/**
 * Page callback: Displays add content links for available content types.
 *
 * Redirects to node/add/[type] if only one content type is available.
 *
 * @return array
 *   A render array for a list of the node types that can be added; however, if
 *   there is only one node type defined for the site, the function redirects
 *   to the node add page for that one node type and does not return at all.
 *
 * @see node_menu()
 *
 * @deprecated Use \Drupal\node\Controller\NodeController::addPage()
 */
function node_add_page() {
  $content = array();
  // Only use node types the user has access to.
  foreach (node_type_get_types() as $type) {
    if (node_access('create', $type->type)) {
      $content[$type->type] = $type;
    }
  }
  // Bypass the node/add listing if only one content type is available.
  if (count($content) == 1) {
    $type = array_shift($content);
    return new RedirectResponse(url('node/add/' . $type->type, array('absolute' => TRUE)));
  }
  return array('#theme' => 'node_add_list', '#content' => $content);
}

/**
 * Returns HTML for a list of available node types for node creation.
 *
 * @param $variables
 *   An associative array containing:
 *   - content: An array of content types.
 *
 * @see node_add_page()
 *
 * @ingroup themeable
 */
function theme_node_add_list($variables) {
  $content = $variables['content'];

  if ($content) {
    $output = '<dl class="node-type-list">';
    foreach ($content as $type) {
      $output .= '<dt>' . l($type->name, 'node/add/' . $type->type) . '</dt>';
      $output .= '<dd>' . filter_xss_admin($type->description) . '</dd>';
    }
    $output .= '</dl>';
  }
  else {
    $output = '<p>' . t('You have not created any content types yet. Go to the <a href="@create-content">content type creation page</a> to add a new content type.', array('@create-content' => url('admin/structure/types/add'))) . '</p>';
  }
  return $output;
}


/**
 * Page callback: Provides the node submission form.
 *
 * @param $node_type
 *   The node type object for the submitted node.
 *
 * @return array
 *   A node submission form.
 *
 * @see node_menu()
 *
 * @deprecated Use \Drupal\node\Controller\NodeController::add()
 */
function node_add($node_type) {
  $user = \Drupal::currentUser();

  $type = $node_type->type;
  $langcode = module_invoke('language', 'get_default_langcode', 'node', $type);
  $node = entity_create('node', array(
    'uid' => $user->id(),
    'name' => $user->getUsername(),
    'type' => $type,
    'langcode' => $langcode ? $langcode : language_default()->id,
  ));
  drupal_set_title(t('Create @name', array('@name' => $node_type->name)), PASS_THROUGH);
  return \Drupal::entityManager()->getForm($node);
}

/**
 * Generates a node preview.
 *
 * @param \Drupal\Core\Entity\EntityInterface $node
 *   The node to preview.
 *
 * @return
 *   An HTML-formatted string of a node preview.
 *
 * @see node_form_build_preview()
 */
function node_preview(NodeInterface $node) {
  if (node_access('create', $node) || node_access('update', $node)) {

    $node->changed = REQUEST_TIME;

    // Display a preview of the node.
    if (!form_get_errors()) {
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
 * Returns HTML for a node preview for display during node creation and editing.
 *
 * @param $variables
 *   An associative array containing:
 *   - node: The node entity which is being previewed.
 *
 * @see NodeFormController::preview()
 * @see node_preview()
 *
 * @ingroup themeable
 */
function theme_node_preview($variables) {
  $node = $variables['node'];

  $output = '';

  $elements = node_view($node, 'teaser');
  $elements['#attached']['library'][] = array('node', 'drupal.node.preview');
  $trimmed = drupal_render($elements);
  $elements = node_view($node, 'full');
  $full = drupal_render($elements);

  // Do we need to preview trimmed version of post as well as full version?
  if ($trimmed != $full) {
    drupal_set_message(t('The trimmed version of your post shows what your post looks like when promoted to the main page or when exported for syndication.<span class="no-js"> You can insert the delimiter "&lt;!--break--&gt;" (without the quotes) to fine-tune where your post gets split.</span>'));
    $output .= '<h3>' . t('Preview trimmed version') . '</h3>';
    $output .= $trimmed;
    $output .= '<h3>' . t('Preview full version') . '</h3>';
    $output .= $full;
  }
  else {
    $output .= $full;
  }

  return $output;
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
 * @deprecated Use \Drupal\node\Controller\NodeController::revisionOverview()
 */
function node_revision_overview($node) {
  drupal_set_title(t('Revisions for %title', array('%title' => $node->label())), PASS_THROUGH);

  $header = array(t('Revision'), t('Operations'));

  $revisions = node_revision_list($node);

  $rows = array();
  $type = $node->getType();

  $revert_permission = FALSE;
  if ((user_access("revert $type revisions") || user_access('revert all revisions') || user_access('administer nodes')) && node_access('update', $node)) {
    $revert_permission = TRUE;
  }
  $delete_permission = FALSE;
  if ((user_access("delete $type revisions") || user_access('delete all revisions') || user_access('administer nodes')) && node_access('delete', $node)) {
    $delete_permission = TRUE;
  }
  foreach ($revisions as $revision) {
    $row = array();
    if ($revision->current_vid > 0) {
      $username = array(
        '#theme' => 'username',
        '#account' => user_load($revision->uid),
      );
      $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->revision_timestamp, 'short'), 'node/' . $node->id()), '!username' => drupal_render($username)))
                               . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : ''),
                     'class' => array('revision-current'));
      $row[] = array('data' => drupal_placeholder(t('current revision')), 'class' => array('revision-current'));
    }
    else {
      $username = array(
        '#theme' => 'username',
        '#account' => user_load($revision->uid),
      );
      $row[] = t('!date by !username', array('!date' => l(format_date($revision->revision_timestamp, 'short'), "node/" . $node->id() . "/revisions/" . $revision->vid . "/view"), '!username' => drupal_render($username)))
               . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : '');
      if ($revert_permission) {
        $links['revert'] = array(
          'title' => t('Revert'),
          'href' => "node/" . $node->id() . "/revisions/" . $revision->vid . "/revert",
        );
      }
      if ($delete_permission) {
        $links['delete'] = array(
          'title' => t('Delete'),
          'href' => "node/" . $node->id() . "/revisions/" . $revision->vid . "/delete",
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

  $build['node_revisions_table'] = array(
    '#theme' => 'table',
    '#rows' => $rows,
    '#header' => $header,
    '#attached' => array (
      'css' => array(drupal_get_path('module', 'node') . '/css/node.admin.css'),
    ),
  );

  return $build;
}
