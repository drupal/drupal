<?php
// $Id: forum.admin.inc,v 1.33 2010/04/24 14:49:13 dries Exp $

/**
 * @file
 * Administrative page callbacks for the forum module.
 */
function forum_form_main($type, $edit = array()) {
  $edit = (array) $edit;
  if ((isset($_POST['op']) && $_POST['op'] == t('Delete')) || !empty($_POST['confirm'])) {
    return drupal_get_form('forum_confirm_delete', $edit['tid']);
  }
  switch ($type) {
    case 'forum':
      return drupal_get_form('forum_form_forum', $edit);
      break;
    case 'container':
      return drupal_get_form('forum_form_container', $edit);
      break;
  }
}

/**
 * Returns a form for adding a forum to the forum vocabulary
 *
 * @param $edit Associative array containing a forum term to be added or edited.
 * @ingroup forms
 * @see forum_form_submit()
 */
function forum_form_forum($form, &$form_state, $edit = array()) {
  $edit += array(
    'name' => '',
    'description' => '',
    'tid' => NULL,
    'weight' => 0,
  );
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Forum name'),
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#description' => t('Short but meaningful name for this collection of threaded discussions.'),
    '#required' => TRUE,
  );
  $form['description'] = array('#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('Description and guidelines for discussions within this forum.'),
  );
  $form['parent']['#tree'] = TRUE;
  $form['parent'][0] = _forum_parent_select($edit['tid'], t('Parent'), 'forum');
  $form['weight'] = array('#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('Forums are displayed in ascending order by weight (forums with equal weights are displayed alphabetically).'),
  );

  $form['vid'] = array('#type' => 'hidden', '#value' => variable_get('forum_nav_vocabulary', ''));
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit' ] = array('#type' => 'submit', '#value' => t('Save'));
  if ($edit['tid']) {
    $form['actions']['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['tid'] = array('#type' => 'hidden', '#value' => $edit['tid']);
  }
  $form['#submit'][] = 'forum_form_submit';
  $form['#theme'] = 'forum_form';

  return $form;
}

/**
 * Process forum form and container form submissions.
 */
function forum_form_submit($form, &$form_state) {
  if ($form['form_id']['#value'] == 'forum_form_container') {
    $container = TRUE;
    $type = t('forum container');
  }
  else {
    $container = FALSE;
    $type = t('forum');
  }

  $term = (object) $form_state['values'];
  $status = taxonomy_term_save($term);
  switch ($status) {
    case SAVED_NEW:
      if ($container) {
        $containers = variable_get('forum_containers', array());
        $containers[] = $term->tid;
        variable_set('forum_containers', $containers);
      }
      drupal_set_message(t('Created new @type %term.', array('%term' => $form_state['values']['name'], '@type' => $type)));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('The @type %term has been updated.', array('%term' => $form_state['values']['name'], '@type' => $type)));
      // Clear the page and block caches to avoid stale data.
      cache_clear_all();
      break;
  }
  $form_state['redirect'] = 'admin/structure/forum';
  return;
}

/**
 * Returns HTML for a forum form.
 *
 * By default this does not alter the appearance of a form at all,
 * but is provided as a convenience for themers.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_forum_form($variables) {
  return drupal_render_children($variables['form']);
}

/**
 * Returns a form for adding a container to the forum vocabulary
 *
 * @param $edit Associative array containing a container term to be added or edited.
 * @ingroup forms
 * @see forum_form_submit()
 */
function forum_form_container($form, &$form_state, $edit = array()) {
  $edit += array(
    'name' => '',
    'description' => '',
    'tid' => NULL,
    'weight' => 0,
  );
  // Handle a delete operation.
  $form['name'] = array(
    '#title' => t('Container name'),
    '#type' => 'textfield',
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#description' => t('Short but meaningful name for this collection of related forums.'),
    '#required' => TRUE
  );

  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('Description and guidelines for forums within this container.')
  );
  $form['parent']['#tree'] = TRUE;
  $form['parent'][0] = _forum_parent_select($edit['tid'], t('Parent'), 'container');
  $form['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('Containers are displayed in ascending order by weight (containers with equal weights are displayed alphabetically).')
  );

  $form['vid'] = array(
    '#type' => 'hidden',
    '#value' => variable_get('forum_nav_vocabulary', ''),
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save')
  );
  if ($edit['tid']) {
    $form['actions']['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['tid'] = array('#type' => 'value', '#value' => $edit['tid']);
  }
  $form['#submit'][] = 'forum_form_submit';
  $form['#theme'] = 'forum_form';

  return $form;
}

/**
 * Returns a confirmation page for deleting a forum taxonomy term.
 *
 * @param $tid ID of the term to be deleted
 */
function forum_confirm_delete($form, &$form_state, $tid) {
  $term = taxonomy_term_load($tid);

  $form['tid'] = array('#type' => 'value', '#value' => $tid);
  $form['name'] = array('#type' => 'value', '#value' => $term->name);

  return confirm_form($form, t('Are you sure you want to delete the forum %name?', array('%name' => $term->name)), 'admin/structure/forum', t('Deleting a forum or container will also delete its sub-forums, if any. To delete posts in this forum, visit <a href="@content">content administration</a> first. This action cannot be undone.', array('@content' => url('admin/content'))), t('Delete'), t('Cancel'));
}

/**
 * Implement forms api _submit call. Deletes a forum after confirmation.
 */
function forum_confirm_delete_submit($form, &$form_state) {
  taxonomy_term_delete($form_state['values']['tid']);
  drupal_set_message(t('The forum %term and all sub-forums have been deleted.', array('%term' => $form_state['values']['name'])));
  watchdog('content', 'forum: deleted %term and all its sub-forums.', array('%term' => $form_state['values']['name']));

  $form_state['redirect'] = 'admin/structure/forum';
  return;
}

/**
 * Form builder for the forum settings page.
 *
 * @see system_settings_form()
 */
function forum_admin_settings($form) {
  $number = drupal_map_assoc(array(5, 10, 15, 20, 25, 30, 35, 40, 50, 60, 80, 100, 150, 200, 250, 300, 350, 400, 500));
  $form['forum_hot_topic'] = array('#type' => 'select',
    '#title' => t('Hot topic threshold'),
    '#default_value' => 15,
    '#options' => $number,
    '#description' => t('The number of replies a topic must have to be considered "hot".'),
  );
  $number = drupal_map_assoc(array(10, 25, 50, 75, 100));
  $form['forum_per_page'] = array('#type' => 'select',
    '#title' => t('Topics per page'),
    '#default_value' => 25,
    '#options' => $number,
    '#description' => t('Default number of forum topics displayed per page.'),
  );
  $forder = array(1 => t('Date - newest first'), 2 => t('Date - oldest first'), 3 => t('Posts - most active first'), 4 => t('Posts - least active first'));
  $form['forum_order'] = array('#type' => 'radios',
    '#title' => t('Default order'),
    '#default_value' => '1',
    '#options' => $forder,
    '#description' => t('Default display order for topics.'),
  );
  return system_settings_form($form, TRUE);
}

/**
 * Returns an overview list of existing forums and containers
 */
function forum_overview($form, &$form_state) {
  module_load_include('inc', 'taxonomy', 'taxonomy.admin');

  $vid = variable_get('forum_nav_vocabulary', '');
  $vocabulary = taxonomy_vocabulary_load($vid);
  $form = taxonomy_overview_terms($form, $form_state, $vocabulary);

  foreach (element_children($form) as $key) {
    if (isset($form[$key]['#term'])) {
      $term = $form[$key]['#term'];
      $form[$key]['view']['#href'] = 'forum/' . $term['tid'];
      if (in_array($form[$key]['#term']['tid'], variable_get('forum_containers', array()))) {
        $form[$key]['edit']['#title'] = t('edit container');
        $form[$key]['edit']['#href'] = 'admin/structure/forum/edit/container/' . $term['tid'];
      }
      else {
        $form[$key]['edit']['#title'] = t('edit forum');
        $form[$key]['edit']['#href'] = 'admin/structure/forum/edit/forum/' . $term['tid'];
      }
    }
  }

  // Remove the alphabetical reset.
  unset($form['reset_alphabetical']);

  // The form needs to have submit and validate handlers set explicitly.
  $form['#theme'] = 'taxonomy_overview_terms';
  $form['#submit'] = array('taxonomy_overview_terms_submit'); // Use the existing taxonomy overview submit handler.
  $form['#empty_text'] = t('No containers or forums available. <a href="@container">Add container</a> or <a href="@forum">Add forum</a>.', array('@container' => url('admin/structure/forum/add/container'), '@forum' => url('admin/structure/forum/add/forum')));
  return $form;
}

/**
 * Returns a select box for available parent terms
 *
 * @param $tid ID of the term which is being added or edited
 * @param $title Title to display the select box with
 * @param $child_type Whether the child is forum or container
 */
function _forum_parent_select($tid, $title, $child_type) {

  $parents = taxonomy_get_parents($tid);
  if ($parents) {
    $parent = array_shift($parents);
    $parent = $parent->tid;
  }
  else {
    $parent = 0;
  }

  $vid = variable_get('forum_nav_vocabulary', '');
  $children = taxonomy_get_tree($vid, $tid);

  // A term can't be the child of itself, nor of its children.
  foreach ($children as $child) {
    $exclude[] = $child->tid;
  }
  $exclude[] = $tid;

  $tree = taxonomy_get_tree($vid);
  $options[0] = '<' . t('root') . '>';
  if ($tree) {
    foreach ($tree as $term) {
      if (!in_array($term->tid, $exclude)) {
        $options[$term->tid] = str_repeat(' -- ', $term->depth) . $term->name;
      }
    }
  }
  if ($child_type == 'container') {
    $description = t('Containers are usually placed at the top (root) level, but may also be placed inside another container or forum.');
  }
  elseif ($child_type == 'forum') {
    $description = t('Forums may be placed at the top (root) level, or inside another container or forum.');
  }

  return array('#type' => 'select', '#title' => $title, '#default_value' => $parent, '#options' => $options, '#description' => $description, '#required' => TRUE);
}
