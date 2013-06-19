<?php

/**
 * @file
 * Administrative page callbacks for the shortcut module.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
  $sets = entity_load_multiple('shortcut');
  $current_set = shortcut_current_displayed_set($account);

  $options = array();
  foreach ($sets as $name => $set) {
    $options[$name] = check_plain($set->label());
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
      '#default_value' => $current_set->id(),
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#title_display' => 'invisible',
      '#description' => t('The new set is created by copying items from your default shortcut set.'),
      '#access' => $add_access,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => 'shortcut_set_load',
        'source' => array('label'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ),
      // This id could be used for menu name.
      '#maxlength' => 23,
      '#states' => array(
        'required' => array(
          ':input[name="set"]' => array('value' => 'new'),
        ),
      ),
      '#required' => FALSE,
    );

    if ($user->uid != $account->uid) {
      $default_set = shortcut_default_set($account);
      $form['new']['#description'] = t('The new set is created by copying items from the %default set.', array('%default' => $default_set->label()));
    }

    $form['#attached'] = array(
      'library' => array(array('shortcut', 'drupal.shortcut.admin')),
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
      '#markup' => '<p>' . t('You are currently using the %set-name shortcut set.', array('%set-name' => $current_set->label())) . '</p>',
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
    if (trim($form_state['values']['label']) == '') {
      form_set_error('new', t('The new set label is required.'));
    }
    // Check to prevent a duplicate title.
    if (shortcut_set_title_exists($form_state['values']['label'])) {
      form_set_error('label', t('The shortcut set %name already exists. Choose another name.', array('%name' => $form_state['values']['label'])));
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
    $set = entity_create('shortcut', array(
      'id' => $form_state['values']['id'],
      'label' => $form_state['values']['label'],
      'links' => $default_set->links,
    ));
    $set->save();
    $replacements = array(
      '%user' => $account->name,
      '%set_name' => $set->label(),
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
    $form_state['redirect'] = 'admin/config/user-interface/shortcut/manage/' . $set->id();
  }
  else {
    // Switch to a different shortcut set.
    $set = shortcut_set_load($form_state['values']['set']);
    $replacements = array(
      '%user' => $account->name,
      '%set_name' => $set->label(),
    );
    drupal_set_message($account->uid == $user->uid ? t('You are now using the %set_name shortcut set.', $replacements) : t('%user is now using the %set_name shortcut set.', $replacements));
  }

  // Assign the shortcut set to the provided user account.
  shortcut_set_assign_user($set, $account);
}

/**
 * Page callback: provides the shortcut set creation form.
 */
function shortcut_set_add() {
  $entity = entity_create('shortcut', array());
  return Drupal::entityManager()->getForm($entity);
}

/**
 * Form callback: builds the form for customizing shortcut sets.
 *
 * @param $form
 *   An associative array containing the structure of the form.
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $shortcut_set Drupal\shortcut\Plugin\Core\Entity\Shortcut
 *   An object representing the shortcut set which is being edited.
 *
 * @return
 *   An array representing the form definition.
 *
 * @ingroup forms
 * @see shortcut_set_customize_submit()
 */
function shortcut_set_customize($form, &$form_state, $shortcut_set) {
  $form['#shortcut_set_name'] = $shortcut_set->id();
  $form['shortcuts'] = array(
    '#tree' => TRUE,
    '#weight' => -20,
    'links' => array(),
  );

  foreach ($shortcut_set->links as $uuid => $link) {
    $mlid = $link['mlid'];
    $form['shortcuts']['links'][$mlid]['name']['#markup'] = l($link['link_title'], $link['link_path']);
    $form['shortcuts']['links'][$mlid]['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $link['link_title'])),
      '#title_display' => 'invisible',
      '#delta' => 50,
      '#default_value' => $link['weight'],
      '#attributes' => array('class' => array('shortcut-weight')),
    );

    $links['edit'] = array(
      'title' => t('Edit'),
      'href' => "admin/config/user-interface/shortcut/link/$mlid",
    );
    $links['delete'] = array(
      'title' => t('Delete'),
      'href' => "admin/config/user-interface/shortcut/link/$mlid/delete",
    );
    $form['shortcuts']['links'][$mlid]['operations'] = array(
      '#type' => 'operations',
      '#links' => $links,
    );
  }

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
  foreach ($form_state['values']['shortcuts']['links'] as $mlid => $data) {
    $link = menu_link_load($mlid);
    $link['weight'] = $data['weight'];
    menu_link_save($link);
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

  // Do not add any rows to the table if there are no shortcuts to display.
  $statuses = empty($shortcuts_by_status['enabled']) && empty($shortcuts_by_status['disabled']) ? array() : array_keys($shortcuts_by_status);

  $rows = array();
  drupal_add_tabledrag('shortcuts', 'order', 'sibling', 'shortcut-weight');
  foreach (element_children($form['shortcuts']['links']) as $key) {
    $shortcut = &$form['shortcuts']['links'][$key];
    $row = array();
    $row[] = drupal_render($shortcut['name']);
    $row[] = drupal_render($shortcut['weight']);
    $row[] = drupal_render($shortcut['operations']);
    $rows[] = array(
      'data' => $row,
      'class' => array('draggable'),
    );
  }

  $header = array(t('Name'), t('Weight'), t('Operations'));

  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No shortcuts available. <a href="@link">Add a shortcut</a>.', array(
      '@link' => url('admin/config/user-interface/shortcut/manage/' . $form['#shortcut_set_name'] . '/add-link'),
    )),
    '#attributes' => array(
      'id' => 'shortcuts',
    ),
  );

  $output = drupal_render($table);
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
 * @param $shortcut_set Drupal\shortcut\Plugin\Core\Entity\Shortcut
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
    $shortcut_link = entity_create('menu_link', array(
      'link_title' => '',
      'link_path' => ''
    ));
  }
  else {
    $shortcut_link['link_path'] = ($shortcut_link['link_path'] == '<front>') ? '' : Drupal::service('path.alias_manager')->getPathAlias($shortcut_link['link_path']);
  }

  $form['shortcut_link']['#tree'] = TRUE;
  $form['shortcut_link']['link_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#size' => 40,
    '#maxlength' => 255,
    '#default_value' => $shortcut_link['link_title'],
    '#required' => TRUE,
  );

  $form['shortcut_link']['link_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Path'),
    '#size' => 40,
    '#maxlength' => 255,
    '#field_prefix' => url(NULL, array('absolute' => TRUE)),
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
  $shortcut_path = Drupal::service('path.alias_manager')->getSystemPath($form_state['values']['shortcut_link']['link_path']);
  if (empty($shortcut_path)) {
    $shortcut_path = '<front>';
  }
  $form_state['values']['shortcut_link']['link_path'] = $shortcut_path;

  $shortcut_link = $form_state['values']['original_shortcut_link'];
  foreach ($form_state['values']['shortcut_link'] as $key => $value) {
   $shortcut_link[$key] = $value;
  }

  menu_link_save($shortcut_link);
  $set_name = str_replace('shortcut-', '' , $shortcut_link['menu_name']);
  $form_state['redirect'] = 'admin/config/user-interface/shortcut/manage/' . $set_name;
  drupal_set_message(t('The shortcut %link has been updated.', array('%link' => $shortcut_link['link_title'])));
}

/**
 * Submit handler for shortcut_link_add().
 */
function shortcut_link_add_submit($form, &$form_state) {
  // Add the shortcut link to the set.
  $shortcut_set = $form_state['values']['shortcut_set'];
  $shortcut_link = $form_state['values']['shortcut_link'];
  $shortcut_link['menu_name'] = $shortcut_set->id();
  shortcut_admin_add_link($shortcut_link, $shortcut_set);
  $shortcut_set->save();
  $form_state['redirect'] = 'admin/config/user-interface/shortcut/manage/' . $shortcut_set->id();
  drupal_set_message(t('Added a shortcut for %title.', array('%title' => $shortcut_link['link_title'])));
}

/**
 * Adds a link to the end of a shortcut set, keeping within a prescribed limit.
 *
 * @param $link
 *   An array representing a shortcut link.
 * @param $shortcut_set Drupal\shortcut\Plugin\Core\Entity\Shortcut
 *   An object representing the shortcut set which the link will be added to.
 *   The links in the shortcut set will be re-weighted so that the new link is
 *   at the end, and some existing links may be disabled (if the $limit
 *   parameter is provided).
 */
function shortcut_admin_add_link($shortcut_link, &$shortcut_set) {
  // Normalize the path in case it is an alias.
  $shortcut_link['link_path'] = Drupal::service('path.alias_manager')->getSystemPath($shortcut_link['link_path']);
  if (empty($shortcut_link['link_path'])) {
    $shortcut_link['link_path'] = '<front>';
  }
  $menu_link = entity_create('menu_link', $shortcut_link);
  $menu_link->save();

  // Add the link to the end of the list.
  $shortcut_set->links[$menu_link->uuid()] = $menu_link;
  shortcut_set_reset_link_weights($shortcut_set);
}

/**
 * Menu page callback: creates a new link in the provided shortcut set.
 *
 * After completion, redirects the user back to where they came from.
 *
 * @param $shortcut_set Drupal\shortcut\Plugin\Core\Entity\Shortcut
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
    shortcut_admin_add_link($link, $shortcut_set);
    if ($shortcut_set->save() == SAVED_UPDATED) {
      drupal_set_message(t('Added a shortcut for %title.', array('%title' => $link['link_title'])));
    }
    else {
      drupal_set_message(t('Unable to add a shortcut for %title.', array('%title' => $link['link_title'])));
    }
    return new RedirectResponse(url('<front>', array('absolute' => TRUE)));
  }

  throw new AccessDeniedHttpException();
}
