<?php

/**
 * @file
 * Administrative page callbacks for the shortcut module.
 */

/**
 * Returns the maximum number of shortcut "slots" available per shortcut set.
 *
 * This is used as a limitation in the user interface only.
 *
 * @return
 *   The maximum number of shortcuts allowed to be added to a shortcut set.
 */
function shortcut_max_slots() {
  return variable_get('shortcut_max_slots', 7);
}

/**
 * Form callback: builds the form for switching shortcut sets.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $account
 *   (optional) The user account whose shortcuts will be switched. Defaults to
 *   the current logged-in user.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_switch_validate()
 * @see shortcut_set_switch_submit()
 */
function shortcut_set_switch($form, &$form_state, $account = NULL) {
  global $user;
  if (!isset($account)) {
    $account = $user;
  }

  // Prepare the list of shortcut sets.
  $sets = shortcut_sets();
  $current_set = shortcut_current_displayed_set($account);

  $options = array();
  foreach ($sets as $name => $set) {
    $options[$name] = check_plain($set->title);
  }

  // Only administrators can add shortcut sets.
  $add_access = user_access('administer shortcuts');
  if ($add_access) {
    $options['new'] = t('New set');
  }

  if (count($options) > 1) {
    $form['account'] = array(
      '#type' => 'value',
      '#value' => $account,
    );

    $form['set'] = array(
      '#type' => 'radios',
      '#title' => $user->uid == $account->uid ? t('Choose a set of shortcuts to use') : t('Choose a set of shortcuts for this user'),
      '#options' => $options,
      '#default_value' => $current_set->set_name,
    );

    $form['new'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#title_display' => 'invisible',
      '#description' => t('The new set is created by copying items from your default shortcut set.'),
      '#access' => $add_access,
    );

    if ($user->uid != $account->uid) {
      $default_set = shortcut_default_set($account);
      $form['new']['#description'] = t('The new set is created by copying items from the %default set.', array('%default' => $default_set->title));
    }

    $form['#attached'] = array(
      'css' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.css'),
      'js' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.js'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Change set'),
    );
  }
  else {
    // There is only 1 option, so output a message in the $form array.
    $form['info'] = array(
      '#markup' => '<p>' . t('You are currently using the %set-name shortcut set.', array('%set-name' => $current_set->title)) . '</p>',
    );
  }

  return $form;
}

/**
 * Validation handler for shortcut_set_switch().
 */
function shortcut_set_switch_validate($form, &$form_state) {
  if ($form_state['values']['set'] == 'new') {
    // Check to prevent creating a shortcut set with an empty title.
    if (trim($form_state['values']['new']) == '') {
      form_set_error('new', t('The new set name is required.'));
    }
    // Check to prevent a duplicate title.
    if (shortcut_set_title_exists($form_state['values']['new'])) {
      form_set_error('new', t('The shortcut set %name already exists. Choose another name.', array('%name' => $form_state['values']['new'])));
    }
  }
}

/**
 * Submit handler for shortcut_set_switch().
 */
function shortcut_set_switch_submit($form, &$form_state) {
  global $user;
  $account = $form_state['values']['account'];

  if ($form_state['values']['set'] == 'new') {
    // Save a new shortcut set with links copied from the user's default set.
    $default_set = shortcut_default_set($account);
    $set = (object) array(
      'title' => $form_state['values']['new'],
      'links' => menu_links_clone($default_set->links),
    );
    shortcut_set_save($set);
    $replacements = array(
      '%user' => $account->name,
      '%set_name' => $set->title,
      '@switch-url' => url(current_path()),
    );
    if ($account->uid == $user->uid) {
      // Only administrators can create new shortcut sets, so we know they have
      // access to switch back.
      drupal_set_message(t('You are now using the new %set_name shortcut set. You can edit it from this page or <a href="@switch-url">switch back to a different one.</a>', $replacements));
    }
    else {
      drupal_set_message(t('%user is now using a new shortcut set called %set_name. You can edit it from this page.', $replacements));
    }
    $form_state['redirect'] = 'admin/config/user-interface/shortcut/' . $set->set_name;
  }
  else {
    // Switch to a different shortcut set.
    $set = shortcut_set_load($form_state['values']['set']);
    $replacements = array(
      '%user' => $account->name,
      '%set_name' => $set->title,
    );
    drupal_set_message($account->uid == $user->uid ? t('You are now using the %set_name shortcut set.', $replacements) : t('%user is now using the %set_name shortcut set.', $replacements));
  }

  // Assign the shortcut set to the provided user account.
  shortcut_set_assign_user($set, $account);
}

/**
 * Menu page callback: builds the page for administering shortcut sets.
 */
function shortcut_set_admin() {
  $shortcut_sets = shortcut_sets();
  $header = array(t('Name'), array('data' => t('Operations'), 'colspan' => 4));

  $rows = array();
  foreach ($shortcut_sets as $set) {
    $row = array(
      check_plain($set->title),
      l(t('list links'), "admin/config/user-interface/shortcut/$set->set_name"),
      l(t('edit set name'), "admin/config/user-interface/shortcut/$set->set_name/edit"),
    );
    if (shortcut_set_delete_access($set)) {
      $row[] = l(t('delete set'), "admin/config/user-interface/shortcut/$set->set_name/delete");
    }
    else {
      $row[] = '';
    }

    $rows[] = $row;
  }

  return theme('table', array('header' => $header, 'rows' => $rows));
}

/**
 * Form callback: builds the form for adding a shortcut set.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_add_form_validate()
 * @see shortcut_set_add_form_submit()
 */
function shortcut_set_add_form($form, &$form_state) {
  $form['new'] = array(
    '#type' => 'textfield',
    '#title' => t('Set name'),
    '#description' => t('The new set is created by copying items from your default shortcut set.'),
    '#required' => TRUE,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Create new set'),
  );

  return $form;
}

/**
 * Validation handler for shortcut_set_add_form().
 */
function shortcut_set_add_form_validate($form, &$form_state) {
  // Check to prevent a duplicate title.
  if (shortcut_set_title_exists($form_state['values']['new'])) {
    form_set_error('new', t('The shortcut set %name already exists. Choose another name.', array('%name' => $form_state['values']['new'])));
  }
}

/**
 * Submit handler for shortcut_set_add_form().
 */
function shortcut_set_add_form_submit($form, &$form_state) {
  // Save a new shortcut set with links copied from the user's default set.
  $default_set = shortcut_default_set();
  $set = (object) array(
    'title' => $form_state['values']['new'],
    'links' => menu_links_clone($default_set->links),
  );
  shortcut_set_save($set);
  drupal_set_message(t('The %set_name shortcut set has been created. You can edit it from this page.', array('%set_name' => $set->title)));
  $form_state['redirect'] = 'admin/config/user-interface/shortcut/' . $set->set_name;
}

/**
 * Form callback: builds the form for customizing shortcut sets.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_set
 *   An object representing the shortcut set which is being edited.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_customize_submit()
 */
function shortcut_set_customize($form, &$form_state, $shortcut_set) {
  $form['#shortcut_set_name'] = $shortcut_set->set_name;
  $form['shortcuts'] = array(
    '#tree' => TRUE,
    '#weight' => -20,
    'enabled' => array(),
    'disabled' => array(),
  );

  foreach ($shortcut_set->links as $link) {
    $mlid = $link['mlid'];
    $status = $link['hidden'] ? 'disabled' : 'enabled';
    $form['shortcuts'][$status][$mlid]['name']['#markup'] = l($link['link_title'], $link['link_path']);
    $form['shortcuts'][$status][$mlid]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#delta' => 50,
      '#default_value' => $link['weight'],
      '#attributes' => array('class' => array('shortcut-weight')),
    );
    $form['shortcuts'][$status][$mlid]['status'] = array(
      '#type' => 'select',
      '#title' => t('Status'),
      '#options' => array('disabled' => t('Disabled'), 'enabled' => t('Enabled')),
      '#default_value' => $status,
      '#attributes' => array('class' => array('shortcut-status-select')),
    );

    $form['shortcuts'][$status][$mlid]['edit']['#markup'] = l(t('edit'), 'admin/config/user-interface/shortcut/link/' . $mlid);
    $form['shortcuts'][$status][$mlid]['delete']['#markup'] = l(t('delete'), 'admin/config/user-interface/shortcut/link/' . $mlid . '/delete');
  }

  $form['#attached'] = array(
    'css' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.css'),
    'js' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.js'),
  );

  $form['actions'] = array(
    '#type' => 'actions',
    '#access' => !empty($shortcut_set->links),
  );
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save changes'),
  );

  return $form;
}

/**
 * Submit handler for shortcut_set_customize().
 */
function shortcut_set_customize_submit($form, &$form_state) {
  foreach ($form_state['values']['shortcuts'] as $group => $links) {
    foreach ($links as $mlid => $data) {
      $link = menu_link_load($mlid);
      $link['hidden'] = $data['status'] == 'enabled' ? 0 : 1;
      $link['weight'] = $data['weight'];
      menu_link_save($link);
    }
  }
  drupal_set_message(t('The shortcut set has been updated.'));
}

/**
 * Returns HTML for a shortcut set customization form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @see shortcut_set_customize()
 * @ingroup themeable
 */
function theme_shortcut_set_customize($variables) {
  $form = $variables['form'];
  $map = array('disabled' => t('Disabled'), 'enabled' => t('Enabled'));
  $shortcuts_by_status = array(
    'enabled' => element_children($form['shortcuts']['enabled']),
    'disabled' => element_children($form['shortcuts']['disabled']),
  );
  // Do not add any rows to the table if there are no shortcuts to display.
  $statuses = empty($shortcuts_by_status['enabled']) && empty($shortcuts_by_status['disabled']) ? array() : array_keys($shortcuts_by_status);

  $rows = array();
  foreach ($statuses as $status) {
    drupal_add_tabledrag('shortcuts', 'match', 'sibling', 'shortcut-status-select');
    drupal_add_tabledrag('shortcuts', 'order', 'sibling', 'shortcut-weight');
    $rows[] = array(
      'data' => array(array(
        'colspan' => 5,
        'data' => '<strong>' . $map[$status] . '</strong>',
      )),
      'class' => array('shortcut-status', 'shortcut-status-' . $status),
    );

    foreach ($shortcuts_by_status[$status] as $key) {
      $shortcut = &$form['shortcuts'][$status][$key];
      $row = array();
      $row[] = drupal_render($shortcut['name']);
      $row[] = drupal_render($shortcut['weight']);
      $row[] = drupal_render($shortcut['status']);
      $row[] = drupal_render($shortcut['edit']);
      $row[] = drupal_render($shortcut['delete']);
      $rows[] = array(
        'data' => $row,
        'class' => array('draggable'),
      );
    }

    if ($status == 'enabled') {
      for ($i = 0; $i < shortcut_max_slots(); $i++) {
        $rows['empty-' . $i] = array(
          'data' => array(array(
            'colspan' => 5,
            'data' => '<em>' . t('Empty') . '</em>',
          )),
          'class' => array('shortcut-slot-empty'),
        );
      }
      $count_shortcuts = count($shortcuts_by_status[$status]);
      if (!empty($count_shortcuts)) {
        for ($i = 0; $i < min($count_shortcuts, shortcut_max_slots()); $i++) {
          $rows['empty-' . $i]['class'][] = 'shortcut-slot-hidden';
        }
      }
    }
  }

  $header = array(t('Name'), t('Weight'), t('Status'), array('data' => t('Operations'), 'colspan' => 2));
  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'shortcuts'), 'empty' => t('No shortcuts available. <a href="@link">Add a shortcut</a>.', array('@link' => url('admin/config/user-interface/shortcut/' . $form['#shortcut_set_name'] . '/add-link')))));
  $output .= drupal_render($form['actions']);
  $output = drupal_render_children($form) . $output;
  return $output;
}

/**
 * Form callback: builds the form for adding a new shortcut link.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_set
 *   An object representing the shortcut set to which the link will be added.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_link_edit_validate()
 * @see shortcut_link_add_submit()
 */
function shortcut_link_add($form, &$form_state, $shortcut_set) {
  drupal_set_title(t('Add new shortcut'));
  $form['shortcut_set'] = array(
    '#type' => 'value',
    '#value' => $shortcut_set,
  );
  $form += _shortcut_link_form_elements();
  return $form;
}

/**
 * Form callback: builds the form for editing a shortcut link.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_link
 *   An array representing the link that is being edited.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_link_edit_validate()
 * @see shortcut_link_edit_submit()
 */
function shortcut_link_edit($form, &$form_state, $shortcut_link) {
  drupal_set_title(t('Editing @shortcut', array('@shortcut' => $shortcut_link['link_title'])));
  $form['original_shortcut_link'] = array(
    '#type' => 'value',
    '#value' => $shortcut_link,
  );
  $form += _shortcut_link_form_elements($shortcut_link);
  return $form;
}

/**
 * Helper function for building a form for adding or editing shortcut links.
 *
 * @param $shortcut_link
 *   (optional) An array representing the shortcut link that will be edited. If
 *   not provided, a new link will be created.
 *
 * @return
 *   An array of form elements.
 */
function _shortcut_link_form_elements($shortcut_link = NULL) {
  if (!isset($shortcut_link)) {
    $shortcut_link = array(
      'link_title' => '',
      'link_path' => ''
    );
  }
  else {
    $shortcut_link['link_path'] = ($shortcut_link['link_path'] == '<front>') ? '' : drupal_get_path_alias($shortcut_link['link_path']);
  }

  $form['shortcut_link']['#tree'] = TRUE;
  $form['shortcut_link']['link_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#description' => t('The name of the shortcut.'),
    '#size' => 40,
    '#maxlength' => 255,
    '#default_value' => $shortcut_link['link_title'],
    '#required' => TRUE,
  );

  $form['shortcut_link']['link_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Path'),
    '#description' => t('The path to the shortcut.'),
    '#size' => 40,
    '#maxlength' => 255,
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
    '#default_value' => $shortcut_link['link_path'],
  );

  $form['#validate'][] = 'shortcut_link_edit_validate';

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Validation handler for the shortcut link add and edit forms.
 */
function shortcut_link_edit_validate($form, &$form_state) {
  if (!shortcut_valid_link($form_state['values']['shortcut_link']['link_path'])) {
    form_set_error('shortcut_link][link_path', t('The link must correspond to a valid path on the site.'));
  }
}

/**
 * Submit handler for shortcut_link_edit().
 */
function shortcut_link_edit_submit($form, &$form_state) {
  // Normalize the path in case it is an alias.
  $shortcut_path = drupal_get_normal_path($form_state['values']['shortcut_link']['link_path']);
  if (empty($shortcut_path)) {
    $shortcut_path = '<front>';
  }
  $form_state['values']['shortcut_link']['link_path'] = $shortcut_path;

  $shortcut_link = array_merge($form_state['values']['original_shortcut_link'], $form_state['values']['shortcut_link']);

  menu_link_save($shortcut_link);
  $form_state['redirect'] = 'admin/config/user-interface/shortcut/' . $shortcut_link['menu_name'];
  drupal_set_message(t('The shortcut %link has been updated.', array('%link' => $shortcut_link['link_title'])));
}

/**
 * Submit handler for shortcut_link_add().
 */
function shortcut_link_add_submit($form, &$form_state) {
  // Add the shortcut link to the set.
  $shortcut_set = $form_state['values']['shortcut_set'];
  $shortcut_link = $form_state['values']['shortcut_link'];
  $shortcut_link['menu_name'] = $shortcut_set->set_name;
  shortcut_admin_add_link($shortcut_link, $shortcut_set, shortcut_max_slots());
  shortcut_set_save($shortcut_set);
  $form_state['redirect'] = 'admin/config/user-interface/shortcut/' . $shortcut_link['menu_name'];
  drupal_set_message(t('Added a shortcut for %title.', array('%title' => $shortcut_link['link_title'])));
}

/**
 * Adds a link to the end of a shortcut set, keeping within a prescribed limit.
 *
 * @param $link
 *   An array representing a shortcut link.
 * @param $shortcut_set
 *   An object representing the shortcut set which the link will be added to.
 *   The links in the shortcut set will be re-weighted so that the new link is
 *   at the end, and some existing links may be disabled (if the $limit
 *   parameter is provided).
 * @param $limit
 *   (optional) The maximum number of links that are allowed to be enabled for
 *   this shortcut set. If provided, existing links at the end of the list that
 *   exceed the limit will be automatically disabled. If not provided, no limit
 *   will be enforced.
 */
function shortcut_admin_add_link($shortcut_link, &$shortcut_set, $limit = NULL) {
  if (isset($limit)) {
    // Disable any existing links at the end of the list that would cause the
    // limit to be exceeded. Take into account whether or not the new link will
    // be enabled and count towards the total.
    $number_enabled = !empty($shortcut_link['hidden']) ? 0 : 1;
    foreach ($shortcut_set->links as &$link) {
      if (!$link['hidden']) {
        $number_enabled++;
        if ($number_enabled > $limit) {
          $link['hidden'] = 1;
        }
      }
    }
  }

  // Normalize the path in case it is an alias.
  $shortcut_link['link_path'] = drupal_get_normal_path($shortcut_link['link_path']);
  if (empty($shortcut_link['link_path'])) {
    $shortcut_link['link_path'] = '<front>';
  }

  // Add the link to the end of the list.
  $shortcut_set->links[] = $shortcut_link;
  shortcut_set_reset_link_weights($shortcut_set);
}

/**
 * Form callback: builds the form for editing the shortcut set name.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param object $shortcut_set
 *   An object representing the shortcut set, as returned from
 *   shortcut_set_load().
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_edit_form_validate()
 * @see shortcut_set_edit_form_submit()
 */
function shortcut_set_edit_form($form, &$form_state, $shortcut_set) {
  $form['shortcut_set'] = array(
    '#type' => 'value',
    '#value' => $shortcut_set,
  );
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Set name'),
    '#default_value' => $shortcut_set->title,
    '#maxlength' => 255,
    '#required' => TRUE,
    '#weight' => -5,
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 5,
  );

  return $form;
}

/**
 * Validation handler for shortcut_set_edit_form().
 */
function shortcut_set_edit_form_validate($form, &$form_state) {
  // Check to prevent a duplicate title, if the title was edited from its
  // original value.
  if ($form_state['values']['title'] != $form_state['values']['shortcut_set']->title && shortcut_set_title_exists($form_state['values']['title'])) {
    form_set_error('title', t('The shortcut set %name already exists. Choose another name.', array('%name' => $form_state['values']['title'])));
  }
}

/**
 * Submit handler for shortcut_set_edit_form().
 */
function shortcut_set_edit_form_submit($form, &$form_state) {
  $shortcut_set = $form_state['values']['shortcut_set'];
  $shortcut_set->title = $form_state['values']['title'];
  shortcut_set_save($shortcut_set);
  drupal_set_message(t('Updated set name to %set-name.', array('%set-name' => $shortcut_set->title)));
  $form_state['redirect'] = "admin/config/user-interface/shortcut/$shortcut_set->set_name";
}

/**
 * Form callback: builds the confirmation form for deleting a shortcut set.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param object $shortcut_set
 *   An object representing the shortcut set, as returned from
 *   shortcut_set_load().
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_delete_form_submit()
 */
function shortcut_set_delete_form($form, &$form_state, $shortcut_set) {
  $form['shortcut_set'] = array(
    '#type' => 'value',
    '#value' => $shortcut_set->set_name,
  );

  // Find out how many users are directly assigned to this shortcut set, and
  // make a message.
  $number = db_query('SELECT COUNT(*) FROM {shortcut_set_users} WHERE set_name = :name', array(':name' => $shortcut_set->set_name))->fetchField();
  $info = '';
  if ($number) {
    $info .= '<p>' . format_plural($number,
      '1 user has chosen or been assigned to this shortcut set.',
      '@count users have chosen or been assigned to this shortcut set.') . '</p>';
  }

  // Also, if a module implements hook_shortcut_default_set(), it's possible
  // that this set is being used as a default set. Add a message about that too.
  if (count(module_implements('shortcut_default_set')) > 0) {
    $info .= '<p>' . t('If you have chosen this shortcut set as the default for some or all users, they may also be affected by deleting it.') . '</p>';
  }

  $form['info'] = array(
    '#markup' => $info,
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete the shortcut set %title?', array('%title' => $shortcut_set->title)),
    'admin/config/user-interface/shortcut/' . $shortcut_set->set_name,
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Submit handler for shortcut_set_delete_form().
 */
function shortcut_set_delete_form_submit($form, &$form_state) {
  $shortcut_set = shortcut_set_load($form_state['values']['shortcut_set']);
  shortcut_set_delete($shortcut_set);
  $form_state['redirect'] = 'admin/config/user-interface/shortcut';
  drupal_set_message(t('The shortcut set %title has been deleted.', array('%title' => $shortcut_set->title)));
}

/**
 * Form callback: builds the confirmation form for deleting a shortcut link.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_link
 *   An array representing the link that will be deleted.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_link_delete_submit()
 */
function shortcut_link_delete($form, &$form_state, $shortcut_link) {
  $form['shortcut_link'] = array(
    '#type' => 'value',
    '#value' => $shortcut_link,
  );

  return confirm_form(
    $form,
    t('Are you sure you want to delete the shortcut %title?', array('%title' => $shortcut_link['link_title'])),
    'admin/config/user-interface/shortcut/' . $shortcut_link['menu_name'],
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Submit handler for shortcut_link_delete_submit().
 */
function shortcut_link_delete_submit($form, &$form_state) {
  $shortcut_link = $form_state['values']['shortcut_link'];
  menu_link_delete($shortcut_link['mlid']);
  $form_state['redirect'] = 'admin/config/user-interface/shortcut/' . $shortcut_link['menu_name'];
  drupal_set_message(t('The shortcut %title has been deleted.', array('%title' => $shortcut_link['link_title'])));
}

/**
 * Menu page callback: creates a new link in the provided shortcut set.
 *
 * After completion, redirects the user back to where they came from.
 *
 * @param $shortcut_set
 *   Returned from shortcut_set_load().
 */
function shortcut_link_add_inline($shortcut_set) {
  if (isset($_REQUEST['token']) && drupal_valid_token($_REQUEST['token'], 'shortcut-add-link') && shortcut_valid_link($_GET['link'])) {
    $item = menu_get_item($_GET['link']);
    $title = ($item && $item['title']) ? $item['title'] : $_GET['name'];
    $link = array(
      'link_title' => $title,
      'link_path' => $_GET['link'],
    );
    shortcut_admin_add_link($link, $shortcut_set, shortcut_max_slots());
    if (shortcut_set_save($shortcut_set)) {
      drupal_set_message(t('Added a shortcut for %title.', array('%title' => $link['link_title'])));
    }
    else {
      drupal_set_message(t('Unable to add a shortcut for %title.', array('%title' => $link['link_title'])));
    }
    drupal_goto();
  }

  return drupal_access_denied();
}
