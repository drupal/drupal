<?php

/**
 * @file
 * Callbacks for adding, editing, and deleting content and managing revisions.
 *
 * Also includes validation, submission and other helper functions.
 *
 * @see node_menu()
 */

use Drupal\node\Node;

/**
 * Page callback: Presents the node editing form.
 *
 * @see node_menu()
 */
function node_page_edit($node) {
  $type_name = node_type_get_name($node);
  drupal_set_title(t('<em>Edit @type</em> @title', array('@type' => $type_name, '@title' => $node->label())), PASS_THROUGH);
  return entity_get_form($node);
}

/**
 * Page callback: Displays add content links for available content types.
 *
 * Redirects to node/add/[type] if only one content type is available.
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
 * @return
 *   Returns a node submission form.
 *
 * @see node_menu()
 */
function node_add($node_type) {
  global $user;

  $type = $node_type->type;
  $node = entity_create('node', array(
    'uid' => $user->uid,
    'name' => (isset($user->name) ? $user->name : ''),
    'type' => $type,
    'langcode' => node_type_get_default_langcode($type)
  ));
  drupal_set_title(t('Create @name', array('@name' => $node_type->name)), PASS_THROUGH);
  $output = entity_get_form($node);

  return $output;
}

/**
 * Generates a node preview.
 *
 * @param Drupal\node\Node $node
 *   The node to preview.
 *
 * @return
 *   Themed node preview.
 *
 * @see node_form_build_preview()
 */
function node_preview(Node $node) {
  if (node_access('create', $node) || node_access('update', $node)) {
    _field_invoke_multiple('load', 'node', array($node->nid => $node));
    // Load the user's name when needed.
    if (isset($node->name)) {
      // The use of isset() is mandatory in the context of user IDs, because
      // user ID 0 denotes the anonymous user.
      if ($user = user_load_by_name($node->name)) {
        $node->uid = $user->uid;
        $node->picture = $user->picture;
      }
      else {
        $node->uid = 0; // anonymous user
      }
    }
    elseif ($node->uid) {
      $user = user_load($node->uid);
      $node->name = $user->name;
      $node->picture = $user->picture;
    }

    $node->changed = REQUEST_TIME;
    $nodes = array($node->nid => $node);
    field_attach_prepare_view('node', $nodes, 'full');

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
 * @ingroup themeable
 */
function theme_node_preview($variables) {
  $node = $variables['node'];

  $output = '';

  $preview_trimmed_version = FALSE;

  $elements = node_view(clone $node, 'teaser');
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
    drupal_set_message(t('@type %title has been deleted.', array('@type' => node_type_get_name($node), '%title' => $node->label())));
  }

  $form_state['redirect'] = '<front>';
}

/**
 * Page callback: Generates an overview table of older revisions of a node.
 *
 * @see node_menu()
 */
function node_revision_overview($node) {
  drupal_set_title(t('Revisions for %title', array('%title' => $node->label())), PASS_THROUGH);

  $header = array(t('Revision'), array('data' => t('Operations'), 'colspan' => 2));

  $revisions = node_revision_list($node);

  $rows = array();
  $revert_permission = FALSE;
  if ((user_access('revert revisions') || user_access('administer nodes')) && node_access('update', $node)) {
    $revert_permission = TRUE;
  }
  $delete_permission = FALSE;
  if ((user_access('delete revisions') || user_access('administer nodes')) && node_access('delete', $node)) {
    $delete_permission = TRUE;
  }
  foreach ($revisions as $revision) {
    $row = array();
    $operations = array();

    if ($revision->current_vid > 0) {
      $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'short'), "node/$node->nid"), '!username' => theme('username', array('account' => $revision))))
                               . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : ''),
                     'class' => array('revision-current'));
      $operations[] = array('data' => drupal_placeholder(t('current revision')), 'class' => array('revision-current'), 'colspan' => 2);
    }
    else {
      $row[] = t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'short'), "node/$node->nid/revisions/$revision->vid/view"), '!username' => theme('username', array('account' => $revision))))
               . (($revision->log != '') ? '<p class="revision-log">' . filter_xss($revision->log) . '</p>' : '');
      if ($revert_permission) {
        $operations[] = l(t('revert'), "node/$node->nid/revisions/$revision->vid/revert");
      }
      if ($delete_permission) {
        $operations[] = l(t('delete'), "node/$node->nid/revisions/$revision->vid/delete");
      }
    }
    $rows[] = array_merge($row, $operations);
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
 * @see node_menu()
 * @see node_revision_revert_confirm_submit()
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
  $node_revision->revision = 1;

  // The revision timestamp will be updated when the revision is saved. Keep the
  // original one for the confirmation message.
  $original_revision_timestamp = $node_revision->revision_timestamp;

  $node_revision->log = t('Copy of the revision from %date.', array('%date' => format_date($original_revision_timestamp)));

  $node_revision->save();

  watchdog('content', '@type: reverted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->label(), '%revision' => $node_revision->vid));
  drupal_set_message(t('@type %title has been reverted back to the revision from %revision-date.', array('@type' => node_type_get_name($node_revision), '%title' => $node_revision->label(), '%revision-date' => format_date($original_revision_timestamp))));
  $form_state['redirect'] = 'node/' . $node_revision->nid . '/revisions';
}

/**
 * Page callback: Form constructor for the revision deletion confirmation form.
 *
 * This form prevents against CSRF attacks.
 *
 * @see node_menu()
 * @see node_revision_delete_confirm_submit()
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
  drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($node_revision->revision_timestamp), '@type' => node_type_get_name($node_revision), '%title' => $node_revision->label())));
  $form_state['redirect'] = 'node/' . $node_revision->nid;
  if (db_query('SELECT COUNT(vid) FROM {node_revision} WHERE nid = :nid', array(':nid' => $node_revision->nid))->fetchField() > 1) {
    $form_state['redirect'] .= '/revisions';
  }
}
