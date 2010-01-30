<?php
// $Id$

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
 * Menu callback; Build the form for switching shortcut sets.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $account
 *   (optional) The user account whose shortcuts will be switched. Defaults to
 *   the current logged-in user.
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
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
  $default_set = shortcut_default_set($account);
  $options = array();
  foreach ($sets as $name => $set) {
    $options[$name] = check_plain($set->title);
  }

  // Only administrators can add shortcut sets.
  $add_access = user_access('administer shortcuts');
  if ($add_access) {
    $options['new'] = t('New set');
  }

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
    '#description' => t('The new set is created by copying items from the @default set.', array('@default' => $default_set->title)),
    '#access' => $add_access,
  );

  $form['#attached'] = array(
    'css' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.css'),
    'js' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.js'),
  );

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Submit handler for the form that switches shortcut sets.
 */
function shortcut_set_switch_submit($form, &$form_state) {
  global $user;
  $account = $form_state['values']['account'];

  if ($form_state['values']['set'] == 'new') {
    // Save a new shortcut set with links copied from the default set.
    $default_set = shortcut_default_set();
    $set = (object) array(
      'title' => $form_state['values']['new'],
      'links' => menu_links_clone($default_set->links),
    );
    shortcut_set_save($set);
    $replacements = array(
      '%user' => $account->name,
      '%set_name' => $set->title,
      // This form can be displayed on more than one page, so make sure we link
      // back to the correct one.
      '@switch-url' => url($_GET['q']),
    );
    if ($account->uid == $user->uid) {
      // Only administrators can create new shortcut sets, so we know they have
      // access to switch back.
      drupal_set_message(t('You are now using the new %set_name shortcut set. You can edit it from this page or <a href="@switch-url">switch back to a different one.</a>', $replacements));
    }
    else {
      drupal_set_message(t('%user is now using a new shortcut set called %set_name. You can edit it from this page.', $replacements));
    }
    $form_state['redirect'] = 'admin/config/system/shortcut/' . $set->set_name;
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
 * Menu callback; Build the form for customizing shortcut sets.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_set
 *   An object representing the shortcut set which is being edited.
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_customize_submit()
 */
function shortcut_set_customize($form, &$form_state, $shortcut_set) {
  $form['set'] = array(
    '#markup' => t('Using set "@set"', array('@set' => $shortcut_set->title)),
    '#prefix' => '<h4 class="shortcuts-set">',
    '#suffix' => '</h4>',
    '#weight' => -100,
  );

  $form['change_set'] = array(
    '#type' => 'link',
    '#title' => t('Change set'),
    '#href' => 'admin/config/system/shortcut',
    '#prefix' => '<div class="shortcuts-change-set"> (',
    '#suffix' => ')</div>',
    '#weight' => -99,
    '#access' => shortcut_set_switch_access(),
  );

  $form['shortcuts']['#tree'] = TRUE;
  $form['shortcuts']['enabled'] = $form['shortcuts']['disabled'] = array();
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

    $form['shortcuts'][$status][$mlid]['edit']['#markup'] = l(t('edit'), 'admin/config/system/shortcut/link/' . $mlid);
    $form['shortcuts'][$status][$mlid]['delete']['#markup'] = l(t('delete'), 'admin/config/system/shortcut/link/' . $mlid . '/delete');
  }

  $form['#attached'] = array(
    'css' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.css'),
    'js' => array(drupal_get_path('module', 'shortcut') . '/shortcut.admin.js'),
  );

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Changes'),
  );

  return $form;
}

/**
 * Submit handler for the shortcut set customization form.
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
 * Theme function for the shortcut set customization form.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: An array representing the form.
 * @return
 *   A themed HTML string representing the content of the form.
 *
 * @ingroup themeable
 * @see shortcut_set_customize()
 */
function theme_shortcut_set_customize($variables) {
  $form = $variables['form'];
  $map = array('disabled' => t('Disabled'), 'enabled' => t('Enabled'));

  $rows = array();
  foreach (array('enabled', 'disabled') as $status) {
    drupal_add_tabledrag('shortcuts', 'match', 'sibling', 'shortcut-status-select');
    drupal_add_tabledrag('shortcuts', 'order', 'sibling', 'shortcut-weight');
    $rows[] = array(
      'data' => array(array(
        'colspan' => 5,
        'data' => '<strong>' . $map[$status] . '</strong>',
      )),
      'class' => array('shortcut-status', 'shortcut-status-' . $status),
    );
    foreach (element_children($form['shortcuts'][$status]) as $key) {
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
      $count_shortcuts = count(element_children($form['shortcuts'][$status]));
      if (!empty($count_shortcuts)) {
        for ($i = 0; $i < min($count_shortcuts, shortcut_max_slots()); $i++) {
          $rows['empty-' . $i]['class'][] = 'shortcut-slot-hidden';
        }
      }
    }
  }
  $header = array(t('Name'), t('Weight'), t('Status'), array('data' => t('Operations'), 'colspan' => 2));
  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'shortcuts')));
  $output .= drupal_render($form['submit']);
  $output = drupal_render_children($form) . $output;
  return $output;
}

/**
 * Menu callback; Build the form for adding a new shortcut link.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_set
 *   An object representing the shortcut set to which the link will be added.
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
 * Menu callback; Build the form for editing a shortcut link.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_link
 *   An array representing the link that is being edited.
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

  $form['shortcut_link']['#tree'] = TRUE;
  $form['shortcut_link']['link_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#description' => t('The name of the shortcut.'),
    '#size' => 40,
    '#maxlength' => 255,
    '#default_value' => $shortcut_link['link_title'],
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

  $form['submit'] = array(
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
 * Submit handler for the shortcut link editing form.
 */
function shortcut_link_edit_submit($form, &$form_state) {
  $shortcut_link = array_merge($form_state['values']['original_shortcut_link'], $form_state['values']['shortcut_link']);
  menu_link_save($shortcut_link);
  $form_state['redirect'] = 'admin/config/system/shortcut/' . $shortcut_link['menu_name'];
  drupal_set_message(t('The shortcut %link has been updated.', array('%link' => $shortcut_link['link_title'])));
}

/**
 * Submit handler for the form that adds shortcut links.
 */
function shortcut_link_add_submit($form, &$form_state) {
  // Add the shortcut link to the set.
  $shortcut_set = $form_state['values']['shortcut_set'];
  $shortcut_link = $form_state['values']['shortcut_link'];
  $shortcut_link['menu_name'] = $shortcut_set->set_name;
  shortcut_admin_add_link($shortcut_link, $shortcut_set, shortcut_max_slots());
  shortcut_set_save($shortcut_set);
  $form_state['redirect'] = 'admin/config/system/shortcut/' . $shortcut_link['menu_name'];
  drupal_set_message(t('Added a shortcut for %title.', array('%title' => $shortcut_link['link_title'])));
}

/**
 * Add a link to the end of a shortcut set, keeping within a prescribed limit.
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

  // Add the link to the end of the list.
  $shortcut_set->links[] = $shortcut_link;
  shortcut_set_reset_link_weights($shortcut_set);
}

/**
 * Menu callback; Build the form for deleting a shortcut link.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_link
 *   An array representing the link that will be deleted.
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
    'admin/config/system/shortcut/' . $shortcut_link['menu_name'],
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Submit handler for the shortcut link deletion form.
 */
function shortcut_link_delete_submit($form, &$form_state) {
  $shortcut_link = $form_state['values']['shortcut_link'];
  menu_link_delete($shortcut_link['mlid']);
  $form_state['redirect'] = 'admin/config/system/shortcut/' . $shortcut_link['menu_name'];
  drupal_set_message(t('The shortcut %title has been deleted.', array('%title' => $shortcut_link['link_title'])));
}

/**
 * Menu callback; Creates a new link in the provided shortcut set
 *
 * After completion, redirects the user back to where they came from.
 *
 * @param $shortcut_set
 *   Returned from shortcut_set_load().
 */
function shortcut_link_add_inline($shortcut_set) {
  if (isset($_REQUEST['token']) && drupal_valid_token($_REQUEST['token'], 'shortcut-add-link') && shortcut_valid_link($_GET['link'])) {
    $link = array(
      'link_title' => $_GET['name'],
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
