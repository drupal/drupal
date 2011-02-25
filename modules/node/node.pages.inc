<?php

/**
 * @file
 * Page callbacks for adding, editing, deleting, and revisions management for content.
 */


/**
 * Menu callback; presents the node editing form, or redirects to delete confirmation.
 */
function node_page_edit($node) {
  drupal_set_title(check_plain($node->title));
  return drupal_get_form($node->type .'_node_form', $node);
}

function node_add_page() {
  $item = menu_get_item();
  $content = system_admin_menu_block($item);
  return theme('node_add_list', $content);
}

/**
 * Display the list of available node types for node creation.
 *
 * @ingroup themeable
 */
function theme_node_add_list($content) {
  $output = '';

  if ($content) {
    $output = '<dl class="node-type-list">';
    foreach ($content as $item) {
      $output .= '<dt>'. l($item['title'], $item['href'], $item['localized_options']) .'</dt>';      
      $output .= '<dd>'. filter_xss_admin($item['description']) .'</dd>';
    }
    $output .= '</dl>';
  }
  return $output;
}


/**
 * Present a node submission form or a set of links to such forms.
 */
function node_add($type) {
  global $user;

  $types = node_get_types();
  $type = isset($type) ? str_replace('-', '_', $type) : NULL;
  // If a node type has been specified, validate its existence.
  if (isset($types[$type]) && node_access('create', $type)) {
    // Initialize settings:
    $node = array('uid' => $user->uid, 'name' => (isset($user->name) ? $user->name : ''), 'type' => $type, 'language' => '');

    drupal_set_title(t('Create @name', array('@name' => $types[$type]->name)));
    $output = drupal_get_form($type .'_node_form', $node);
  }

  return $output;
}

function node_form_validate($form, &$form_state) {
  node_validate($form_state['values'], $form);
}

function node_object_prepare(&$node) {
  // Set up default values, if required.
  $node_options = variable_get('node_options_'. $node->type, array('status', 'promote'));
  // If this is a new node, fill in the default values.
  if (!isset($node->nid)) {
    foreach (array('status', 'promote', 'sticky') as $key) {
      $node->$key = in_array($key, $node_options);
    }
    global $user;
    $node->uid = $user->uid;
    $node->created = time();
  }
  else {
    $node->date = format_date($node->created, 'custom', 'Y-m-d H:i:s O');
    // Remove the log message from the original node object.
    $node->log = NULL;
  }
  // Always use the default revision setting.
  $node->revision = in_array('revision', $node_options);

  node_invoke($node, 'prepare');
  node_invoke_nodeapi($node, 'prepare');
}

/**
 * Generate the node add/edit form array.
 */
function node_form(&$form_state, $node) {
  global $user;

  if (isset($form_state['node'])) {
    $node = $form_state['node'] + (array)$node;
  }
  if (isset($form_state['node_preview'])) {
    $form['#prefix'] = $form_state['node_preview'];
  }
  $node = (object)$node;
  foreach (array('body', 'title', 'format') as $key) {
    if (!isset($node->$key)) {
      $node->$key = NULL;
    }
  }
  if (!isset($form_state['node_preview'])) {
    node_object_prepare($node);
  }
  else {
    $node->build_mode = NODE_BUILD_PREVIEW;
  }

  // Set the id of the top-level form tag
  $form['#id'] = 'node-form';

  // Basic node information.
  // These elements are just values so they are not even sent to the client.
  foreach (array('nid', 'vid', 'uid', 'created', 'type', 'language') as $key) {
    $form[$key] = array(
      '#type' => 'value',
      '#value' => isset($node->$key) ? $node->$key : NULL,
    );
  }

  // Changed must be sent to the client, for later overwrite error checking.
  $form['changed'] = array(
    '#type' => 'hidden',
    '#default_value' => isset($node->changed) ? $node->changed : NULL,
  );
  // Get the node-specific bits.
  if ($extra = node_invoke($node, 'form', $form_state)) {
    $form = array_merge_recursive($form, $extra);
  }
  if (!isset($form['title']['#weight'])) {
    $form['title']['#weight'] = -5;
  }

  $form['#node'] = $node;

  // Add a log field if the "Create new revision" option is checked, or if the
  // current user has the ability to check that option.
  if (!empty($node->revision) || user_access('administer nodes')) {
    $form['revision_information'] = array(
      '#type' => 'fieldset',
      '#title' => t('Revision information'),
      '#collapsible' => TRUE,
      // Collapsed by default when "Create new revision" is unchecked
      '#collapsed' => !$node->revision,
      '#weight' => 20,
    );
    $form['revision_information']['revision'] = array(
      '#access' => user_access('administer nodes'),
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $node->revision,
    );
    $form['revision_information']['log'] = array(
      '#type' => 'textarea',
      '#title' => t('Log message'),
      '#default_value' => (isset($node->log) ? $node->log : ''),
      '#rows' => 2,
      '#description' => t('An explanation of the additions or updates being made to help other authors understand your motivations.'),
    );
  }

  // Node author information for administrators
  $form['author'] = array(
    '#type' => 'fieldset',
    '#access' => user_access('administer nodes'),
    '#title' => t('Authoring information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => 20,
  );
  $form['author']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Authored by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => $node->name ? $node->name : '',
    '#weight' => -1,
    '#description' => t('Leave blank for %anonymous.', array('%anonymous' => variable_get('anonymous', t('Anonymous')))),
  );
  $form['author']['date'] = array(
    '#type' => 'textfield',
    '#title' => t('Authored on'),
    '#maxlength' => 25,
    '#description' => t('Format: %time. Leave blank to use the time of form submission.', array('%time' => !empty($node->date) ? $node->date : format_date($node->created, 'custom', 'Y-m-d H:i:s O'))),
  );

  if (isset($node->date)) {
    $form['author']['date']['#default_value'] = $node->date;
  }

  // Node options for administrators
  $form['options'] = array(
    '#type' => 'fieldset',
    '#access' => user_access('administer nodes'),
    '#title' => t('Publishing options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => 25,
  );
  $form['options']['status'] = array(
    '#type' => 'checkbox',
    '#title' => t('Published'),
    '#default_value' => $node->status,
  );
  $form['options']['promote'] = array(
    '#type' => 'checkbox',
    '#title' => t('Promoted to front page'),
    '#default_value' => $node->promote,
  );
  $form['options']['sticky'] = array(
    '#type' => 'checkbox',
    '#title' => t('Sticky at top of lists'),
    '#default_value' => $node->sticky,
  );

  // These values are used when the user has no administrator access.
  foreach (array('uid', 'created') as $key) {
    $form[$key] = array(
      '#type' => 'value',
      '#value' => $node->$key,
    );
  }

  // Add the buttons.
  $form['buttons'] = array();
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#access' => !variable_get('node_preview', 0) || (!form_get_errors() && isset($form_state['node_preview'])),
    '#value' => t('Save'),
    '#weight' => 5,
    '#submit' => array('node_form_submit'),
  );
  $form['buttons']['preview'] = array(
    '#type' => 'submit',
    '#value' => t('Preview'),
    '#weight' => 10,
    '#submit' => array('node_form_build_preview'),
  );
  if (!empty($node->nid) && node_access('delete', $node)) {
    $form['buttons']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#weight' => 15,
      '#submit' => array('node_form_delete_submit'),
    );
  }
  $form['#validate'][] = 'node_form_validate';
  $form['#theme'] = array($node->type .'_node_form', 'node_form');
  return $form;
}

/**
 * Return a node body field, with format and teaser.
 */
function node_body_field(&$node, $label, $word_count) {

  // Check if we need to restore the teaser at the beginning of the body.
  $include = !isset($node->teaser) || ($node->teaser == substr($node->body, 0, strlen($node->teaser)));

  $form = array(
    '#after_build' => array('node_teaser_js', 'node_teaser_include_verify'));

  $form['#prefix'] = '<div class="body-field-wrapper">';
  $form['#suffix'] = '</div>';

  $form['teaser_js'] = array(
    '#type' => 'textarea',
    '#rows' => 10,
    '#teaser' => 'edit-body',
    '#teaser_checkbox' => 'edit-teaser-include',
    '#disabled' => TRUE,
  );

  $form['teaser_include'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show summary in full view'),
    '#default_value' => $include,
    '#prefix' => '<div class="teaser-checkbox">',
    '#suffix' => '</div>',
  );

  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => check_plain($label),
    '#default_value' => $include ? $node->body : ($node->teaser . $node->body),
    '#rows' => 20,
    '#required' => ($word_count > 0),
  );

  $form['format'] = filter_form($node->format);

  return $form;
}

/**
 * Button sumit function: handle the 'Delete' button on the node form.
 */
function node_form_delete_submit($form, &$form_state) {
  $destination = '';
  if (isset($_REQUEST['destination'])) {
    $destination = drupal_get_destination();
    unset($_REQUEST['destination']);
  }
  $node = $form['#node'];
  $form_state['redirect'] = array('node/'. $node->nid .'/delete', $destination);
}


function node_form_build_preview($form, &$form_state) {
  $node = node_form_submit_build_node($form, $form_state);
  $form_state['node_preview'] = node_preview($node);
}

/**
 * Present a node submission form.
 *
 * @ingroup themeable
 */
function theme_node_form($form) {
  $output = "\n<div class=\"node-form\">\n";

  // Admin form fields and submit buttons must be rendered first, because
  // they need to go to the bottom of the form, and so should not be part of
  // the catch-all call to drupal_render().
  $admin = '';
  if (isset($form['author'])) {
    $admin .= "    <div class=\"authored\">\n";
    $admin .= drupal_render($form['author']);
    $admin .= "    </div>\n";
  }
  if (isset($form['options'])) {
    $admin .= "    <div class=\"options\">\n";
    $admin .= drupal_render($form['options']);
    $admin .= "    </div>\n";
  }
  $buttons = drupal_render($form['buttons']);

  // Everything else gets rendered here, and is displayed before the admin form
  // field and the submit buttons.
  $output .= "  <div class=\"standard\">\n";
  $output .= drupal_render($form);
  $output .= "  </div>\n";

  if (!empty($admin)) {
    $output .= "  <div class=\"admin\">\n";
    $output .= $admin;
    $output .= "  </div>\n";
  }
  $output .= $buttons;
  $output .= "</div>\n";

  return $output;
}

/**
 * Generate a node preview.
 */
function node_preview($node) {
  if (node_access('create', $node) || node_access('update', $node)) {
    // Load the user's name when needed.
    if (isset($node->name)) {
      // The use of isset() is mandatory in the context of user IDs, because
      // user ID 0 denotes the anonymous user.
      if ($user = user_load(array('name' => $node->name))) {
        $node->uid = $user->uid;
        $node->picture = $user->picture;
      }
      else {
        $node->uid = 0; // anonymous user
      }
    }
    else if ($node->uid) {
      $user = user_load(array('uid' => $node->uid));
      $node->name = $user->name;
      $node->picture = $user->picture;
    }

    $node->changed = time();

    // Extract a teaser, if it hasn't been set (e.g. by a module-provided
    // 'teaser' form item).
    if (!isset($node->teaser)) {
      $node->teaser = empty($node->body) ? '' : node_teaser($node->body, $node->format);
      // Chop off the teaser from the body if needed.
      if (!$node->teaser_include && $node->teaser == substr($node->body, 0, strlen($node->teaser))) {
        $node->body = substr($node->body, strlen($node->teaser));
      }
    }

    // Display a preview of the node.
    // Previewing alters $node so it needs to be cloned.
    if (!form_get_errors()) {
      $cloned_node = drupal_clone($node);
      $cloned_node->build_mode = NODE_BUILD_PREVIEW;
      $output = theme('node_preview', $cloned_node);
    }
    drupal_set_title(t('Preview'));

    return $output;
  }
}

/**
 * Display a node preview for display during node creation and editing.
 *
 * @param $node
 *   The node object which is being previewed.
 *
 * @ingroup themeable
 */
function theme_node_preview($node) {
  $output = '<div class="preview">';

  $preview_trimmed_version = FALSE;
  // Do we need to preview trimmed version of post as well as full version?
  if (isset($node->teaser) && isset($node->body)) {
    $teaser = trim($node->teaser);
    $body = trim(str_replace('<!--break-->', '', $node->body));

    // Preview trimmed version if teaser and body will appear different;
    // also (edge case) if both teaser and body have been specified by the user
    // and are actually the same.
    if ($teaser != $body || ($body && strpos($node->body, '<!--break-->') === 0)) {
      $preview_trimmed_version = TRUE;
    }
  }

  if ($preview_trimmed_version) {
    drupal_set_message(t('The trimmed version of your post shows what your post looks like when promoted to the main page or when exported for syndication.<span class="no-js"> You can insert the delimiter "&lt;!--break--&gt;" (without the quotes) to fine-tune where your post gets split.</span>'));
    $output .= '<h3>'. t('Preview trimmed version') .'</h3>';
    $output .= node_view(drupal_clone($node), 1, FALSE, 0);
    $output .= '<h3>'. t('Preview full version') .'</h3>';
    $output .= node_view($node, 0, FALSE, 0);
  }
  else {
    $output .= node_view($node, 0, FALSE, 0);
  }
  $output .= "</div>\n";

  return $output;
}

function node_form_submit($form, &$form_state) {
  global $user;

  $node = node_form_submit_build_node($form, $form_state);
  $insert = empty($node->nid);
  node_save($node);
  $node_link = l(t('view'), 'node/'. $node->nid);
  $watchdog_args = array('@type' => $node->type, '%title' => $node->title);
  $t_args = array('@type' => node_get_types('name', $node), '%title' => $node->title);

  if ($insert) {
    watchdog('content', '@type: added %title.', $watchdog_args, WATCHDOG_NOTICE, $node_link);
    drupal_set_message(t('@type %title has been created.', $t_args));
  }
  else {
    watchdog('content', '@type: updated %title.', $watchdog_args, WATCHDOG_NOTICE, $node_link);
    drupal_set_message(t('@type %title has been updated.', $t_args));
  }
  if ($node->nid) {
    unset($form_state['rebuild']);
    $form_state['nid'] = $node->nid;
    $form_state['redirect'] = 'node/'. $node->nid;
  }
  else {
    // In the unlikely case something went wrong on save, the node will be
    // rebuilt and node form redisplayed the same way as in preview.
    drupal_set_message(t('The post could not be saved.'), 'error');
  }
}

/**
 * Build a node by processing submitted form values and prepare for a form rebuild.
 */
function node_form_submit_build_node($form, &$form_state) {
  // Unset any button-level handlers, execute all the form-level submit
  // functions to process the form values into an updated node.
  unset($form_state['submit_handlers']);
  form_execute_handlers('submit', $form, $form_state);
  $node = node_submit($form_state['values']);
  $form_state['node'] = (array)$node;
  $form_state['rebuild'] = TRUE;
  return $node;
}

/**
 * Menu callback -- ask for confirmation of node deletion
 */
function node_delete_confirm(&$form_state, $node) {
  $form['nid'] = array(
    '#type' => 'value',
    '#value' => $node->nid,
  );

  return confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $node->title)),
    isset($_GET['destination']) ? $_GET['destination'] : 'node/'. $node->nid,
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Execute node deletion
 */
function node_delete_confirm_submit($form, &$form_state) {
  if ($form_state['values']['confirm']) {
    node_delete($form_state['values']['nid']);
  }

  $form_state['redirect'] = '<front>';
}

/**
 * Generate an overview table of older revisions of a node.
 */
function node_revision_overview($node) {
  drupal_set_title(t('Revisions for %title', array('%title' => $node->title)));

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
      $row[] = array('data' => t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'small'), "node/$node->nid"), '!username' => theme('username', $revision)))
                               . (($revision->log != '') ? '<p class="revision-log">'. filter_xss($revision->log) .'</p>' : ''),
                     'class' => 'revision-current');
      $operations[] = array('data' => theme('placeholder', t('current revision')), 'class' => 'revision-current', 'colspan' => 2);
    }
    else {
      $row[] = t('!date by !username', array('!date' => l(format_date($revision->timestamp, 'small'), "node/$node->nid/revisions/$revision->vid/view"), '!username' => theme('username', $revision)))
               . (($revision->log != '') ? '<p class="revision-log">'. filter_xss($revision->log) .'</p>' : '');
      if ($revert_permission) {
        $operations[] = l(t('revert'), "node/$node->nid/revisions/$revision->vid/revert");
      }
      if ($delete_permission) {
        $operations[] = l(t('delete'), "node/$node->nid/revisions/$revision->vid/delete");
      }
    }
    $rows[] = array_merge($row, $operations);
  }

  return theme('table', $header, $rows);
}

/**
 * Ask for confirmation of the reversion to prevent against CSRF attacks.
 */
function node_revision_revert_confirm($form_state, $node_revision) {
  $form['#node_revision'] = $node_revision;
  return confirm_form($form, t('Are you sure you want to revert to the revision from %revision-date?', array('%revision-date' => format_date($node_revision->revision_timestamp))), 'node/'. $node_revision->nid .'/revisions', '', t('Revert'), t('Cancel'));
}

function node_revision_revert_confirm_submit($form, &$form_state) {
  $node_revision = $form['#node_revision'];
  $node_revision->revision = 1;
  $node_revision->log = t('Copy of the revision from %date.', array('%date' => format_date($node_revision->revision_timestamp)));
  if (module_exists('taxonomy')) {
    $node_revision->taxonomy = array_keys($node_revision->taxonomy);
  }

  node_save($node_revision);

  watchdog('content', '@type: reverted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->title, '%revision' => $node_revision->vid));
  drupal_set_message(t('@type %title has been reverted back to the revision from %revision-date.', array('@type' => node_get_types('name', $node_revision), '%title' => $node_revision->title, '%revision-date' => format_date($node_revision->revision_timestamp))));
  $form_state['redirect'] = 'node/'. $node_revision->nid .'/revisions';
}

function node_revision_delete_confirm($form_state, $node_revision) {
  $form['#node_revision'] = $node_revision;
  return confirm_form($form, t('Are you sure you want to delete the revision from %revision-date?', array('%revision-date' => format_date($node_revision->revision_timestamp))), 'node/'. $node_revision->nid .'/revisions', t('This action cannot be undone.'), t('Delete'), t('Cancel'));
}

function node_revision_delete_confirm_submit($form, &$form_state) {
  $node_revision = $form['#node_revision'];
  db_query("DELETE FROM {node_revisions} WHERE nid = %d AND vid = %d", $node_revision->nid, $node_revision->vid);
  node_invoke_nodeapi($node_revision, 'delete revision');
  watchdog('content', '@type: deleted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->title, '%revision' => $node_revision->vid));
  drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($node_revision->revision_timestamp), '@type' => node_get_types('name', $node_revision), '%title' => $node_revision->title)));
  $form_state['redirect'] = 'node/'. $node_revision->nid;
  if (db_result(db_query('SELECT COUNT(vid) FROM {node_revisions} WHERE nid = %d', $node_revision->nid)) > 1) {
    $form_state['redirect'] .= '/revisions';
  }
}
