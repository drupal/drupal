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
  drupal_set_title(t('<em>Edit @type</em> @title', array('@type' => $type_name, '@title' => $node->title)), PASS_THROUGH);
  return drupal_get_form($node->type . '_node_form', $node);
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
 * @param $type
 *   The node type for the submitted node.
 *
 * @return
 *   Returns a node submission form.
 *
 * @see node_menu()
 */
function node_add($type) {
  global $user;

  $types = node_type_get_types();
  $node = entity_create('node', array(
    'uid' => $user->uid,
    'name' => (isset($user->name) ? $user->name : ''),
    'type' => $type,
    'langcode' => node_type_get_default_langcode($type)
  ));
  drupal_set_title(t('Create @name', array('@name' => $types[$type]->name)), PASS_THROUGH);
  $output = drupal_get_form($type . '_node_form', $node);

  return $output;
}

/**
 * Form validation handler for node_form().
 *
 * @see node_form_delete_submit()
 * @see node_form_build_preview()
 * @see node_form_submit()
 * @see node_form_submit_build_node()
 */
function node_form_validate($form, &$form_state) {
  // $form_state['node'] contains the actual entity being edited, but we must
  // not update it with form values that have not yet been validated, so we
  // create a pseudo-entity to use during validation.
  $node = clone $form_state['node'];
  foreach ($form_state['values'] as $key => $value) {
    $node->{$key} = $value;
  }
  node_validate($node, $form, $form_state);
  entity_form_field_validate('node', $form, $form_state);
}

/**
 * Form constructor for the node add/edit form.
 *
 * @see node_form_delete_submit()
 * @see node_form_build_preview()
 * @see node_form_validate()
 * @see node_form_submit()
 * @see node_form_submit_build_node()
 * @ingroup forms
 */
function node_form($form, &$form_state, Node $node) {
  global $user;

  // During initial form build, add the node entity to the form state for use
  // during form building and processing. During a rebuild, use what is in the
  // form state.
  if (!isset($form_state['node'])) {
    node_object_prepare($node);
    $form_state['node'] = $node;
  }
  else {
    $node = $form_state['node'];
  }

  // Some special stuff when previewing a node.
  if (isset($form_state['node_preview'])) {
    $form['#prefix'] = $form_state['node_preview'];
    $node->in_preview = TRUE;
  }
  else {
    unset($node->in_preview);
  }

  // Override the default CSS class name, since the user-defined node type name
  // in 'TYPE-node-form' potentially clashes with third-party class names.
  $form['#attributes']['class'][0] = drupal_html_class('node-' . $node->type . '-form');

  // Basic node information.
  // These elements are just values so they are not even sent to the client.
  foreach (array('nid', 'vid', 'uid', 'created', 'type') as $key) {
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
  // Invoke hook_form() to get the node-specific bits. Can't use node_invoke(),
  // because hook_form() needs to be able to receive $form_state by reference.
  // @todo hook_form() implementations are unable to add #validate or #submit
  //   handlers to the form buttons below. Remove hook_form() entirely.
  $function = node_type_get_base($node) . '_form';
  if (function_exists($function) && ($extra = $function($node, $form_state))) {
    $form = array_merge_recursive($form, $extra);
  }
  // If the node type has a title, and the node type form defined no special
  // weight for it, we default to a weight of -5 for consistency.
  if (isset($form['title']) && !isset($form['title']['#weight'])) {
    $form['title']['#weight'] = -5;
  }
  // @todo D8: Remove. Modules should access the node using $form_state['node'].
  $form['#node'] = $node;

  if (module_exists('language')) {
    $languages = language_list(LANGUAGE_ALL);
    $language_options = array();
    foreach ($languages as $langcode => $language) {
      // Make locked languages appear special in the list.
      $language_options[$langcode] = $language->locked ? t('- @name -', array('@name' => $language->name)) : $language->name;
    }
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#default_value' => $node->langcode,
      '#options' => $language_options,
      '#access' => !variable_get('node_type_language_hidden_' . $node->type, TRUE),
    );
  }
  else {
    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $node->langcode,
    );
  }

  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 99,
  );

  // Add a log field if the "Create new revision" option is checked, or if the
  // current user has the ability to check that option.
  $form['revision_information'] = array(
    '#type' => 'fieldset',
    '#title' => t('Revision information'),
    '#collapsible' => TRUE,
    // Collapsed by default when "Create new revision" is unchecked
    '#collapsed' => !$node->revision,
    '#group' => 'additional_settings',
    '#attributes' => array(
      'class' => array('node-form-revision-information'),
    ),
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'node') . '/node.js'),
    ),
    '#weight' => 20,
    '#access' => $node->revision || user_access('administer nodes'),
  );
  $form['revision_information']['revision'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create new revision'),
    '#default_value' => $node->revision,
    '#access' => user_access('administer nodes'),
  );
  // Check the revision log checkbox when the log textarea is filled in.
  // This must not happen if "Create new revision" is enabled by default, since
  // the state would auto-disable the checkbox otherwise.
  if (!$node->revision) {
    $form['revision_information']['revision']['#states'] = array(
      'checked' => array(
        'textarea[name="log"]' => array('empty' => FALSE),
      ),
    );
  }
  $form['revision_information']['log'] = array(
    '#type' => 'textarea',
    '#title' => t('Revision log message'),
    '#rows' => 4,
    '#default_value' => !empty($node->log) ? $node->log : '',
    '#description' => t('Briefly describe the changes you have made.'),
  );

  // Node author information for administrators
  $form['author'] = array(
    '#type' => 'fieldset',
    '#access' => user_access('administer nodes'),
    '#title' => t('Authoring information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'additional_settings',
    '#attributes' => array(
      'class' => array('node-form-author'),
    ),
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'node') . '/node.js',
        array(
          'type' => 'setting',
          'data' => array('anonymous' => variable_get('anonymous', t('Anonymous'))),
        ),
      ),
    ),
    '#weight' => 90,
  );
  $form['author']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Authored by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => !empty($node->name) ? $node->name : '',
    '#weight' => -1,
    '#description' => t('Leave blank for %anonymous.', array('%anonymous' => variable_get('anonymous', t('Anonymous')))),
  );
  $form['author']['date'] = array(
    '#type' => 'textfield',
    '#title' => t('Authored on'),
    '#maxlength' => 25,
    '#description' => t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array('%time' => !empty($node->date) ? date_format(date_create($node->date), 'Y-m-d H:i:s O') : format_date($node->created, 'custom', 'Y-m-d H:i:s O'), '%timezone' => !empty($node->date) ? date_format(date_create($node->date), 'O') : format_date($node->created, 'custom', 'O'))),
    '#default_value' => !empty($node->date) ? $node->date : '',
  );

  // Node options for administrators
  $form['options'] = array(
    '#type' => 'fieldset',
    '#access' => user_access('administer nodes'),
    '#title' => t('Publishing options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'additional_settings',
    '#attributes' => array(
      'class' => array('node-form-options'),
    ),
    '#attached' => array(
      'js' => array(drupal_get_path('module', 'node') . '/node.js'),
    ),
    '#weight' => 95,
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

  // Add the buttons.
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#access' => variable_get('node_preview_' . $node->type, DRUPAL_OPTIONAL) != DRUPAL_REQUIRED || (!form_get_errors() && isset($form_state['node_preview'])),
    '#value' => t('Save'),
    '#weight' => 5,
    '#submit' => array('node_form_submit'),
  );
  $form['actions']['preview'] = array(
    '#access' => variable_get('node_preview_' . $node->type, DRUPAL_OPTIONAL) != DRUPAL_DISABLED,
    '#type' => 'submit',
    '#value' => t('Preview'),
    '#weight' => 10,
    '#submit' => array('node_form_build_preview'),
  );
  if (!empty($node->nid) && node_access('delete', $node)) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#weight' => 15,
      '#submit' => array('node_form_delete_submit'),
    );
  }
  // This form uses a button-level #submit handler for the form's main submit
  // action. node_form_submit() manually invokes all form-level #submit handlers
  // of the form. Without explicitly setting #submit, Form API would auto-detect
  // node_form_submit() as submit handler, but that is the button-level #submit
  // handler for the 'Save' action. To maintain backwards compatibility, a
  // #submit handler is auto-suggested for custom node type modules.
  $form['#validate'][] = 'node_form_validate';
  if (!isset($form['#submit']) && function_exists($node->type . '_node_form_submit')) {
    $form['#submit'][] = $node->type . '_node_form_submit';
  }
  $form += array('#submit' => array());

  field_attach_form('node', $node, $form, $form_state, $node->langcode);
  return $form;
}

/**
 * Form submission handler for the 'Delete' button for node_form().
 *
 * @see node_form_build_preview()
 * @see node_form_validate()
 * @see node_form_submit()
 * @see node_form_submit_build_node()
 */
function node_form_delete_submit($form, &$form_state) {
  $destination = array();
  if (isset($_GET['destination'])) {
    $destination = drupal_get_destination();
    unset($_GET['destination']);
  }
  $node = $form['#node'];
  $form_state['redirect'] = array('node/' . $node->nid . '/delete', array('query' => $destination));
}

/**
 * Form submission handler for the 'Preview' button for node_form().
 *
 * @see node_form_delete_submit()
 * @see node_form_validate()
 * @see node_form_submit()
 * @see node_form_submit_build_node()
 */
function node_form_build_preview($form, &$form_state) {
  $node = node_form_submit_build_node($form, $form_state);
  $form_state['node_preview'] = node_preview($node);
  $form_state['rebuild'] = TRUE;
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
    drupal_set_title(t('Preview'), PASS_THROUGH);

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
 * @see node_preview()
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
 * Form submission handler that saves the node for node_form().
 *
 * @see node_form_delete_submit()
 * @see node_form_build_preview()
 * @see node_form_validate()
 * @see node_form_submit_build_node()
 */
function node_form_submit($form, &$form_state) {
  // Handle possible field translations first and then build the node from the
  // submitted values.
  node_field_language_form_submit($form, $form_state);
  $node = node_form_submit_build_node($form, $form_state);
  $insert = empty($node->nid);
  $node->save();
  $node_link = l(t('view'), 'node/' . $node->nid);
  $watchdog_args = array('@type' => $node->type, '%title' => $node->title);
  $t_args = array('@type' => node_type_get_name($node), '%title' => $node->title);

  if ($insert) {
    watchdog('content', '@type: added %title.', $watchdog_args, WATCHDOG_NOTICE, $node_link);
    drupal_set_message(t('@type %title has been created.', $t_args));
  }
  else {
    watchdog('content', '@type: updated %title.', $watchdog_args, WATCHDOG_NOTICE, $node_link);
    drupal_set_message(t('@type %title has been updated.', $t_args));
  }
  if ($node->nid) {
    $form_state['values']['nid'] = $node->nid;
    $form_state['nid'] = $node->nid;
    $form_state['redirect'] = 'node/' . $node->nid;
  }
  else {
    // In the unlikely case something went wrong on save, the node will be
    // rebuilt and node form redisplayed the same way as in preview.
    drupal_set_message(t('The post could not be saved.'), 'error');
    $form_state['rebuild'] = TRUE;
  }
  // Clear the page and block caches.
  cache_invalidate(array('content' => TRUE));
}

/**
 * Handles possible node language changes.
 *
 */
function node_field_language_form_submit($form, &$form_state) {
  if (field_has_translation_handler('node', 'node')) {
    $bundle = $form_state['values']['type'];
    $node_language = $form_state['values']['langcode'];

    foreach (field_info_instances('node', $bundle) as $instance) {
      $field_name = $instance['field_name'];
      $field = field_info_field($field_name);
      $previous_langcode = $form[$field_name]['#language'];

      // Handle a possible language change: New language values are inserted,
      // previous ones are deleted.
      if ($field['translatable'] && $previous_langcode != $node_language) {
        $form_state['values'][$field_name][$node_language] = $form_state['values'][$field_name][$previous_langcode];
        $form_state['values'][$field_name][$previous_langcode] = array();
      }
    }
  }
}

/**
 * Updates the form state's node entity by processing this submission's values.
 *
 * This is the default builder function for the node form. It is called
 * during the "Save" and "Preview" submit handlers to retrieve the entity to
 * save or preview. This function can also be called by a "Next" button of a
 * wizard to update the form state's entity with the current step's values
 * before proceeding to the next step.
 *
 * @see node_form()
 * @see node_form_delete_submit()
 * @see node_form_build_preview()
 * @see node_form_validate()
 * @see node_form_submit()
 */
function node_form_submit_build_node($form, &$form_state) {
  // @todo Legacy support for modules that extend the node form with form-level
  //   submit handlers that adjust $form_state['values'] prior to those values
  //   being used to update the entity. Module authors are encouraged to instead
  //   adjust the node directly within a hook_node_submit() implementation. For
  //   Drupal 8, evaluate whether the pattern of triggering form-level submit
  //   handlers during button-level submit processing is worth supporting
  //   properly, and if so, add a Form API function for doing so.
  unset($form_state['submit_handlers']);
  form_execute_handlers('submit', $form, $form_state);

  $node = $form_state['node'];
  entity_form_submit_build_entity('node', $node, $form, $form_state);

  node_submit($node);
  foreach (module_implements('node_submit') as $module) {
    $function = $module . '_node_submit';
    $function($node, $form, $form_state);
  }
  return $node;
}

/**
 * Page callback: Form constructor for node deletion confirmation form.
 *
 * @see node_menu()
 */
function node_delete_confirm($form, &$form_state, $node) {
  $form['#node'] = $node;
  // Always provide entity id in the same form key as in the entity edit form.
  $form['nid'] = array('#type' => 'value', '#value' => $node->nid);
  return confirm_form($form,
    t('Are you sure you want to delete %title?', array('%title' => $node->title)),
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
    watchdog('content', '@type: deleted %title.', array('@type' => $node->type, '%title' => $node->title));
    drupal_set_message(t('@type %title has been deleted.', array('@type' => node_type_get_name($node), '%title' => $node->title)));
  }

  $form_state['redirect'] = '<front>';
}

/**
 * Page callback: Generates an overview table of older revisions of a node.
 *
 * @see node_menu()
 */
function node_revision_overview($node) {
  drupal_set_title(t('Revisions for %title', array('%title' => $node->title)), PASS_THROUGH);

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

  watchdog('content', '@type: reverted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->title, '%revision' => $node_revision->vid));
  drupal_set_message(t('@type %title has been reverted back to the revision from %revision-date.', array('@type' => node_type_get_name($node_revision), '%title' => $node_revision->title, '%revision-date' => format_date($original_revision_timestamp))));
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

  watchdog('content', '@type: deleted %title revision %revision.', array('@type' => $node_revision->type, '%title' => $node_revision->title, '%revision' => $node_revision->vid));
  drupal_set_message(t('Revision from %revision-date of @type %title has been deleted.', array('%revision-date' => format_date($node_revision->revision_timestamp), '@type' => node_type_get_name($node_revision), '%title' => $node_revision->title)));
  $form_state['redirect'] = 'node/' . $node_revision->nid;
  if (db_query('SELECT COUNT(vid) FROM {node_revision} WHERE nid = :nid', array(':nid' => $node_revision->nid))->fetchField() > 1) {
    $form_state['redirect'] .= '/revisions';
  }
}
