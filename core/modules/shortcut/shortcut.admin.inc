<?php

/**
 * @file
 * Administrative page callbacks for the shortcut module.
 */
use Drupal\Component\Utility\String;
use Drupal\shortcut\Entity\ShortcutSet;

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
 * @see shortcut_set_switch_validate()
 * @see shortcut_set_switch_submit()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\shortcut\Form\ShortcutForm::overview().
 */
function shortcut_set_switch($form, &$form_state, $account = NULL) {
  $user = \Drupal::currentUser();

  if (!isset($account)) {
    $account = $user;
  }

  // Prepare the list of shortcut sets.
  $sets = entity_load_multiple('shortcut_set');
  $current_set = shortcut_current_displayed_set($account);

  $options = array();
  foreach ($sets as $name => $set) {
    $options[$name] = String::checkPlain($set->label());
  }

  // Only administrators can add shortcut sets.
  $add_access = $user->hasPermission('administer shortcuts');
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
      '#title' => $user->id() == $account->id() ? t('Choose a set of shortcuts to use') : t('Choose a set of shortcuts for this user'),
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
        'exists' => '\Drupal\shortcut\Entity\ShortcutSet::load',
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

    if ($user->id() != $account->id()) {
      $default_set = shortcut_default_set($account);
      $form['new']['#description'] = t('The new set is created by copying items from the %default set.', array('%default' => $default_set->label()));
    }

    $form['#attached'] = array(
      'library' => array('shortcut/drupal.shortcut.admin'),
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
      form_set_error('label', $form_state, t('The new set label is required.'));
    }
    // Check to prevent a duplicate title.
    if (shortcut_set_title_exists($form_state['values']['label'])) {
      form_set_error('label', $form_state, t('The shortcut set %name already exists. Choose another name.', array('%name' => $form_state['values']['label'])));
    }
  }
}

/**
 * Submit handler for shortcut_set_switch().
 */
function shortcut_set_switch_submit($form, &$form_state) {
  $user = \Drupal::currentUser();
  $account = $form_state['values']['account'];

  if ($form_state['values']['set'] == 'new') {
    // Save a new shortcut set with links copied from the user's default set.
    $set = ShortcutSet::create(array(
      'id' => $form_state['values']['id'],
      'label' => $form_state['values']['label'],
    ));
    $set->save();
    $replacements = array(
      '%user' => $account->getUsername(),
      '%set_name' => $set->label(),
      '@switch-url' => url(current_path()),
    );
    if ($account->id() == $user->id()) {
      // Only administrators can create new shortcut sets, so we know they have
      // access to switch back.
      drupal_set_message(t('You are now using the new %set_name shortcut set. You can edit it from this page or <a href="@switch-url">switch back to a different one.</a>', $replacements));
    }
    else {
      drupal_set_message(t('%user is now using a new shortcut set called %set_name. You can edit it from this page.', $replacements));
    }
    $form_state['redirect_route'] = array(
      'route_name' => 'shortcut.set_customize',
      'route_parameters' => array(
        'shortcut_set' => $set->id(),
      ),
    );
  }
  else {
    // Switch to a different shortcut set.
    $set = ShortcutSet::load($form_state['values']['set']);
    $replacements = array(
      '%user' => $account->getUsername(),
      '%set_name' => $set->label(),
    );
    drupal_set_message($account->id() == $user->id() ? t('You are now using the %set_name shortcut set.', $replacements) : t('%user is now using the %set_name shortcut set.', $replacements));
  }

  // Assign the shortcut set to the provided user account.
  shortcut_set_assign_user($set, $account);
}
