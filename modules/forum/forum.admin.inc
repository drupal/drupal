<?php
// $Id: forum.admin.inc,v 1.1 2007/07/24 18:08:54 dries Exp $

/**
 * @file
 * Administrative page callbacks for the forum module.
 */

function forum_form_main($type, $edit = array()) {
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
 * @see forum_form_submit().
 */
function forum_form_forum(&$form_state, $edit = array()) {
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
    '#description' => t('The forum name is used to identify related discussions.'),
    '#required' => TRUE,
  );
  $form['description'] = array('#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('The forum description can give users more information about the discussion topics it contains.'),
  );
  $form['parent']['#tree'] = TRUE;
  $form['parent'][0] = _forum_parent_select($edit['tid'], t('Parent'), 'forum');
  $form['weight'] = array('#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('When listing forums, those with lighter (smaller) weights get listed before containers with heavier (larger) weights. Forums with equal weights are sorted alphabetically.'),
  );

  $form['vid'] = array('#type' => 'hidden', '#value' => variable_get('forum_nav_vocabulary', ''));
  $form['submit' ] = array('#type' => 'submit', '#value' => t('Save'));
  if ($edit['tid']) {
    $form['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
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

  $status = taxonomy_save_term($form_state['values']);
  switch ($status) {
    case SAVED_NEW:
      if ($container) {
        $containers = variable_get('forum_containers', array());
        $containers[] = $form_state['values']['tid'];
        variable_set('forum_containers', $containers);
      }
      drupal_set_message(t('Created new @type %term.', array('%term' => $form_state['values']['name'], '@type' => $type)));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('The @type %term has been updated.', array('%term' => $form_state['values']['name'], '@type' => $type)));
      break;
  }
  $form_state['redirect'] = 'admin/content/forum';
  return;
}

/**
 * Returns a form for adding a container to the forum vocabulary
 *
 * @param $edit Associative array containing a container term to be added or edited.
 * @ingroup forms
 * @see forum_form_submit().
 */
function forum_form_container(&$form_state, $edit = array()) {
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
    '#description' => t('The container name is used to identify related forums.'),
    '#required' => TRUE
  );

  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('The container description can give users more information about the forums it contains.')
  );
  $form['parent']['#tree'] = TRUE;
  $form['parent'][0] = _forum_parent_select($edit['tid'], t('Parent'), 'container');
  $form['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('When listing containers, those with with light (small) weights get listed before containers with heavier (larger) weights. Containers with equal weights are sorted alphabetically.')
  );

  $form['vid'] = array(
    '#type' => 'hidden',
    '#value' => variable_get('forum_nav_vocabulary', ''),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save')
  );
  if ($edit['tid']) {
    $form['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
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
function forum_confirm_delete(&$form_state, $tid) {
  $term = taxonomy_get_term($tid);

  $form['tid'] = array('#type' => 'value', '#value' => $tid);
  $form['name'] = array('#type' => 'value', '#value' => $term->name);

  return confirm_form($form, t('Are you sure you want to delete the forum %name?', array('%name' => $term->name)), 'admin/content/forum', t('Deleting a forum or container will delete all sub-forums and associated posts as well. This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Implementation of forms api _submit call. Deletes a forum after confirmation.
 */
function forum_confirm_delete_submit($form, &$form_state) {
  taxonomy_del_term($form_state['values']['tid']);
  drupal_set_message(t('The forum %term and all sub-forums and associated posts have been deleted.', array('%term' => $form_state['values']['name'])));
  watchdog('content', 'forum: deleted %term and all its sub-forums and associated posts.', array('%term' => $form_state['values']['name']));

  $form_state['redirect'] = 'admin/content/forum';
  return;
}

/**
 * Form builder for the forum settings page.
 *
 * @see system_settings_form().
 */
function forum_admin_settings() {
  $form = array();
  $number = drupal_map_assoc(array(5, 10, 15, 20, 25, 30, 35, 40, 50, 60, 80, 100, 150, 200, 250, 300, 350, 400, 500));
  $form['forum_hot_topic'] = array('#type' => 'select',
    '#title' => t('Hot topic threshold'),
    '#default_value' => variable_get('forum_hot_topic', 15),
    '#options' => $number,
    '#description' => t('The number of posts a topic must have to be considered hot.'),
  );
  $number = drupal_map_assoc(array(10, 25, 50, 75, 100));
  $form['forum_per_page'] = array('#type' => 'select',
    '#title' => t('Topics per page'),
    '#default_value' => variable_get('forum_per_page', 25),
    '#options' => $number,
    '#description' => t('The default number of topics displayed per page; links to browse older messages are automatically being displayed.'),
  );
  $forder = array(1 => t('Date - newest first'), 2 => t('Date - oldest first'), 3 => t('Posts - most active first'), 4 => t('Posts - least active first'));
  $form['forum_order'] = array('#type' => 'radios',
    '#title' => t('Default order'),
    '#default_value' => variable_get('forum_order', '1'),
    '#options' => $forder,
    '#description' => t('The default display order for topics.'),
  );
  return system_settings_form($form);
}

/**
 * Returns an overview list of existing forums and containers
 */
function forum_overview() {
  $header = array(t('Name'), t('Operations'));

  $vid = variable_get('forum_nav_vocabulary', '');
  $tree = taxonomy_get_tree($vid);
  if ($tree) {
    foreach ($tree as $term) {
      if (in_array($term->tid, variable_get('forum_containers', array()))) {
        $rows[] = array(str_repeat(' -- ', $term->depth) .' '. l($term->name, 'forum/'. $term->tid), l(t('edit container'), 'admin/content/forum/edit/container/'. $term->tid));
      }
      else {
        $rows[] = array(str_repeat(' -- ', $term->depth) .' '. l($term->name, 'forum/'. $term->tid), l(t('edit forum'), 'admin/content/forum/edit/forum/'. $term->tid));
       }

    }
  }
  else {
    $rows[] = array(array('data' => '<em>'. t('There are no existing containers or forums. You may add some on the <a href="@container">add container</a> or <a href="@forum">add forum</a> pages.', array('@container' => url('admin/content/forum/add/container'), '@forum' => url('admin/content/forum/add/forum'))) .'</em>', 'colspan' => 2));
  }
  return theme('table', $header, $rows);
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
  $options[0] = '<'. t('root') .'>';
  if ($tree) {
    foreach ($tree as $term) {
      if (!in_array($term->tid, $exclude)) {
        $options[$term->tid] = str_repeat(' -- ', $term->depth) . $term->name;
      }
    }
  }
  if ($child_type == 'container') {
    $description = t('Containers are usually placed at the top (root) level of your forum but you can also place a container inside a parent container or forum.');
  }
  else if ($child_type == 'forum') {
    $description = t('You may place your forum inside a parent container or forum, or at the top (root) level of your forum.');
  }

  return array('#type' => 'select', '#title' => $title, '#default_value' => $parent, '#options' => $options, '#description' => $description, '#required' => TRUE);
}
