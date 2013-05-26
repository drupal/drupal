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

/**
 * Page callback: Presents the node editing form.
 *
 * @param object $node
 *   A node object.
 *
 * @return array
 *   A form array as expected by drupal_render().
 *
 * @see node_menu()
 */
function node_page_edit($node) {
  drupal_set_title(t('<em>Edit @type</em> @title', array('@type' => node_get_type_label($node), '@title' => $node->label())), PASS_THROUGH);
  return entity_get_form($node);
}

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
 */
function node_add_page() {
  $content = array();
  // Only use node types the user has access to.
  foreach (node_type_get_types() as $type) {
    if (node_hook($type->type, 'form') && node_access('create', $type->type)) {
      $content[$type->type] = $type;
    }
  }
  // Bypass the node/add listing if only one content type is available.
  if (count($content) == 1) {
    $type = array_shift($content);
    drupal_goto('node/add/' . $type->type);
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
  $output = '';

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
 */
function node_add($node_type) {
  global $user;

  $type = $node_type->type;
  $langcode = module_invoke('language', 'get_default_langcode', 'node', $type);
  $node = entity_create('node', array(
    'uid' => $user->uid,
    'name' => (isset($user->name) ? $user->name : ''),
    'type' => $type,
    'langcode' => $langcode ? $langcode : language_default()->langcode,
  ))->getBCEntity();
  drupal_set_title(t('Create @name', array('@name' => $node_type->name)), PASS_THROUGH);
  $output = entity_get_form($node);

  return $output;
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
function node_preview(EntityInterface $node) {
  if (node_access('create', $node) || node_access('update', $node)) {
    _field_invoke_multiple('load', 'node', array($node->nid => $node));
    // Load the user's name when needed.
    if (isset($node->name)) {
      // The use of isset() is mandatory in the context of user IDs, because
      // user ID 0 denotes the anonymous user.
      if ($user = user_load_by_name($node->name)) {
        $node->uid = $user->uid;
      }
      else {
        $node->uid = 0; // anonymous user
      }
    }
    elseif ($node->uid) {
      $user = user_load($node->uid);
      $node->name = $user->name;
    }

    $node->changed = REQUEST_TIME;

    // Display a preview of the node.
    if (!form_get_errors()) {
      $node->in_preview = TRUE;
      $output = theme('node_preview', array('node' => $node));
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
 * Page callback: Form constructor for node deletion confirmation form.
 *
 * @param object $node
 *   A node object.
 *
 * @return array
 *   A form array.
 *
 * @see node_delete_confirm_submit()
 * @see node_menu()
 */
function node_delete_confirm($form, &$form_state, $node) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['nid'] = array('#type' => 'value', '#value' => $node->nid);
  return confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $node->label())),
    'node/' . $node->nid,
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for node_delete_confirm().
 */
function node_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    $node = node_load($form_state['values']['nid']);
    node_delete($form_state['values']['nid']);
    watchdog('content', '@type: deleted %title.', array('@type' => $node->type, '%title' => $node->label()));
    drupal_set_message(t('@type %title has been deleted.', array('@type' => node_get_type_label($node), '%title' => $node->label())));
  }

  $form_state['redirect'] = '<front>';
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
 */
function node_revision_overview($node) {
  drupal_set_title(t('Revisions for %title', array('%title' => $node->label())), PASS_THROUGH);

  $header = array(t('Revision'), t('Operations'));

  $revisions = node_revision_list($node);

  $rows = array();
  $type = $node->type;

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
    $type = $node->type;
    if ($revision->current_vid > 0) {
      $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->revision_timestamp, 'short'), "node/$node->nid"), '!username' => theme('username', array('account' => $revision))))
                               . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : ''),
                     'class' => array('revision-current'));
      $row[] = array('data' => drupal_placeholder(t('current revision')), 'class' => array('revision-current'));
    }
    else {
      $row[] = t('!date by !username', array('!date' => l(format_date($revision->revision_timestamp, 'short'), "node/$node->nid/revisions/$revision->vid/view"), '!username' => theme('username', array('account' => $revision))))
               . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : '');
      if ($revert_permission) {
        $links['revert'] = array(
          'title' => t('Revert'),
          'href' => "node/$node->nid/revisions/$revision->vid/revert",
        );
      }
      if ($delete_permission) {
        $links['delete'] = array(
          'title' => t('Delete'),
          'href' => "node/$node->nid/revisions/$revision->vid/delete",
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
      'css' => array(drupal_get_path('module', 'node') . '/node.admin.css'),
    ),
  );

  return $build;
}

/**
 * Page callback: Form constructor for the reversion confirmation form.
 *
 * This form prevents against CSRF attacks.
 *
 * @param int $node_revision
 *   The node revision ID.
 *
 * @return array
 *   An array as expected by drupal_render().
 *
 * @see node_menu()
 * @see node_revision_revert_confirm_submit()
 * @ingroup forms
 */
function node_revision_revert_confirm($form, $form_state, $node_revision) {
  $form['#node_revision'] = $node_revision;
  return confirm_form($form, t('Are you sure you want to revert to the revision from %revision-date?', array('%revision-date' => format_date($node_revision->revision_timestamp))), 'node/' . $node_revision->nid . '/revisions', '', t('Revert'), t('Cancel'));
}

/**
 * Form submission handler for node_revision_revert_confirm().
 */
function node_revision_revert_confirm_submit($form, &$form_state) {
  $node_revision = $form['#node_revision'];
  $node_revision->setNewRevision();
  // Make this the new default revision for the node.
  $node_revision->isDefaultRevision(TRUE);

  // The revision timestamp will be updated when the revision is saved. Keep the
  // original one for the confirmation message.
  $original_revision_timestamp = $node_revision->revision_timestamp;

  $node_revision->log = t('Copy of the revision from %date.', array('%date' => format_date($original_revision_timestamp)));

  $node_revision->save();

  watchdog('content', '@type: reverted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->label(), '%revision' => $node_revision->vid));
  drupal_set_message(t('@type %title has been reverted back to the revision from %revision-date.', array('@type' => node_get_type_label($node_revision), '%title' => $node_revision->label(), '%revision-date' => format_date($original_revision_timestamp))));
  $form_state['redirect'] = 'node/' . $node_revision->nid . '/revisions';
}

/**
 * Page callback: Form constructor for the revision deletion confirmation form.
 *
 * This form prevents against CSRF attacks.
 *
 * @param $node_revision
 *   The node revision ID.
 *
 * @return
 *   An array as expected by drupal_render().
 *
 * @see node_menu()
 * @see node_revision_delete_confirm_submit()
 * @ingroup forms
 */
function node_revision_delete_confirm($form, $form_state, $node_revision) {
  $form['#node_revision'] = $node_revision;
  return confirm_form($form, t('Are you sure you want to delete the revision from %revision-date?', array('%revision-date' => format_date($node_revision->revision_timestamp))), 'node/' . $node_revision->nid . '/revisions', t('This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Form submission handler for node_revision_delete_confirm().
 */
function node_revision_delete_confirm_submit($form, &$form_state) {
  $node_revision = $form['#node_revision'];
  node_revision_delete($node_revision->vid);

  watchdog('content', '@type: deleted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->label(), '%revision' => $node_revision->vid));
  drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($node_revision->revision_timestamp), '@type' => node_get_type_label($node_revision), '%title' => $node_revision->label())));
  $form_state['redirect'] = 'node/' . $node_revision->nid;
  if (db_query('SELECT COUNT(DISTINCT vid) FROM {node_field_revision} WHERE nid = :nid', array(':nid' => $node_revision->nid))->fetchField() > 1) {
    $form_state['redirect'] .= '/revisions';
  }
}
