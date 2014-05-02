<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * @file
 * Hooks provided by the User module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a newly created user.
 *
 * This hook runs after a new user object has just been instantiated. It can be
 * used to set initial values, e.g. to provide defaults.
 *
 * @param \Drupal\user\UserInterface $user
 *   The user object.
 */
function hook_user_create(\Drupal\user\Entity\User $user) {
  if (!isset($user->foo)) {
    $user->foo = 'some_initial_value';
  }
}

/**
 * Act on user objects when loaded from the database.
 *
 * Due to the static cache in user_load_multiple() you should not use this
 * hook to modify the user properties returned by the {users} table itself
 * since this may result in unreliable results when loading from cache.
 *
 * @param $users
 *   An array of user objects, indexed by uid.
 *
 * @see user_load_multiple()
 * @see profile_user_load()
 */
function hook_user_load($users) {
  $result = db_query('SELECT uid, foo FROM {my_table} WHERE uid IN (:uids)', array(':uids' => array_keys($users)));
  foreach ($result as $record) {
    $users[$record->uid]->foo = $record->foo;
  }
}

/**
 * Act before user deletion.
 *
 * This hook is invoked from user_delete_multiple() before field values are
 * deleted and before the user is actually removed from the database.
 *
 * Modules should additionally implement hook_user_cancel() to process stored
 * user data for other account cancellation methods.
 *
 * @param $account
 *   The account that is about to be deleted.
 *
 * @see hook_user_delete()
 * @see user_delete_multiple()
 */
function hook_user_predelete($account) {
  db_delete('mytable')
    ->condition('uid', $account->id())
    ->execute();
}

/**
 * Respond to user deletion.
 *
 * This hook is invoked from user_delete_multiple() after field values are
 * deleted and after the user has been removed from the database.
 *
 * Modules should additionally implement hook_user_cancel() to process stored
 * user data for other account cancellation methods.
 *
 * @param $account
 *   The account that has been deleted.
 *
 * @see hook_user_predelete()
 * @see user_delete_multiple()
 */
function hook_user_delete($account) {
  drupal_set_message(t('User: @name has been deleted.', array('@name' => $account->getUsername())));
}

/**
 * Act on user account cancellations.
 *
 * This hook is invoked from user_cancel() before a user account is canceled.
 * Depending on the account cancellation method, the module should either do
 * nothing, unpublish content, or anonymize content. See user_cancel_methods()
 * for the list of default account cancellation methods provided by User module.
 * Modules may add further methods via hook_user_cancel_methods_alter().
 *
 * This hook is NOT invoked for the 'user_cancel_delete' account cancellation
 * method. To react to that method, implement hook_user_predelete() or
 * hook_user_delete() instead.
 *
 * Expensive operations should be added to the global account cancellation batch
 * by using batch_set().
 *
 * @param $edit
 *   The array of form values submitted by the user.
 * @param $account
 *   The user object on which the operation is being performed.
 * @param $method
 *   The account cancellation method.
 *
 * @see user_cancel_methods()
 * @see hook_user_cancel_methods_alter()
 */
function hook_user_cancel($edit, $account, $method) {
  switch ($method) {
    case 'user_cancel_block_unpublish':
      // Unpublish nodes (current revisions).
      module_load_include('inc', 'node', 'node.admin');
      $nodes = \Drupal::entityQuery('node')
        ->condition('uid', $user->id())
        ->execute();
      node_mass_update($nodes, array('status' => 0), NULL, TRUE);
      break;

    case 'user_cancel_reassign':
      // Anonymize nodes (current revisions).
      module_load_include('inc', 'node', 'node.admin');
      $nodes = \Drupal::entityQuery('node')
        ->condition('uid', $user->id())
        ->execute();
      node_mass_update($nodes, array('uid' => 0), NULL, TRUE);
      // Anonymize old revisions.
      db_update('node_field_revision')
        ->fields(array('uid' => 0))
        ->condition('uid', $account->id())
        ->execute();
      break;
  }
}

/**
 * Modify account cancellation methods.
 *
 * By implementing this hook, modules are able to add, customize, or remove
 * account cancellation methods. All defined methods are turned into radio
 * button form elements by user_cancel_methods() after this hook is invoked.
 * The following properties can be defined for each method:
 * - title: The radio button's title.
 * - description: (optional) A description to display on the confirmation form
 *   if the user is not allowed to select the account cancellation method. The
 *   description is NOT used for the radio button, but instead should provide
 *   additional explanation to the user seeking to cancel their account.
 * - access: (optional) A boolean value indicating whether the user can access
 *   a method. If 'access' is defined, the method cannot be configured as
 *   default method.
 *
 * @param $methods
 *   An array containing user account cancellation methods, keyed by method id.
 *
 * @see user_cancel_methods()
 * @see user_cancel_confirm_form()
 */
function hook_user_cancel_methods_alter(&$methods) {
  $account = \Drupal::currentUser();
  // Limit access to disable account and unpublish content method.
  $methods['user_cancel_block_unpublish']['access'] = $account->hasPermission('administer site configuration');

  // Remove the content re-assigning method.
  unset($methods['user_cancel_reassign']);

  // Add a custom zero-out method.
  $methods['mymodule_zero_out'] = array(
    'title' => t('Delete the account and remove all content.'),
    'description' => t('All your content will be replaced by empty strings.'),
    // access should be used for administrative methods only.
    'access' => $account->hasPermission('access zero-out account cancellation method'),
  );
}

/**
 * Alter the username that is displayed for a user.
 *
 * Called by user_format_name() to allow modules to alter the username that's
 * displayed. Can be used to ensure user privacy in situations where
 * $account->name is too revealing.
 *
 * @param $name
 *   The string that user_format_name() will return.
 *
 * @param $account
 *   The account object passed to user_format_name().
 *
 * @see user_format_name()
 */
function hook_user_format_name_alter(&$name, $account) {
  // Display the user's uid instead of name.
  if ($account->id()) {
    $name = t('User !uid', array('!uid' => $account->id()));
  }
}

/**
 * Act on a user account being inserted or updated.
 *
 * This hook is invoked before the user account is saved to the database.
 *
 * @param $account
 *   The user account object.
 *
 * @see hook_user_insert()
 * @see hook_user_update()
 */
function hook_user_presave($account) {
  // Ensure that our value is an array.
  if (isset($account->mymodule_foo)) {
    $account->mymodule_foo = (array) $account->mymodule_foo;
  }
}

/**
 * Respond to creation of a new user account.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the insert operation is entirely completed
 * and \Drupal\user\DataStorage::save() goes out of scope. You should
 * not rely on data in the database at this time as it is not updated yet. You
 * should also note that any write/update database queries executed from this hook
 * are also not committed immediately. Check \Drupal\user\DataStorage::save()
 * and db_transaction() for more info.
 *
 * @param $account
 *   The user account object.
 *
 * @see hook_user_presave()
 * @see hook_user_update()
 */
function hook_user_insert($account) {
  db_insert('user_changes')
    ->fields(array(
      'uid' => $account->id(),
      'created' => time(),
    ))
    ->execute();
}

/**
 * Respond to updates to a user account.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the update operation is entirely completed
 * and \Drupal\user\DataStorage::save() goes out of scope. You should not
 * rely on data in the database at this time as it is not updated yet. You should
 * also note that any write/update database queries executed from this hook are
 * also not committed immediately. Check \Drupal\user\DataStorage::save()
 * and db_transaction() for more info.
 *
 * @param $account
 *   The user account object.
 *
 * @see hook_user_presave()
 * @see hook_user_insert()
 */
function hook_user_update($account) {
  db_insert('user_changes')
    ->fields(array(
      'uid' => $account->id(),
      'changed' => time(),
    ))
    ->execute();
}

/**
 * The user just logged in.
 *
 * @param $account
 *   The user object on which the operation was just performed.
 */
function hook_user_login($account) {
  $config = \Drupal::config('system.date');
  // If the user has a NULL time zone, notify them to set a time zone.
  if (!$account->getTimezone() && $config->get('timezone.user.configurable') && $config->get('timezone.user.warn')) {
    drupal_set_message(t('Configure your <a href="@user-edit">account time zone setting</a>.', array('@user-edit' => url("user/" . $account->id() . "/edit", array('query' => drupal_get_destination(), 'fragment' => 'edit-timezone')))));
  }
}

/**
 * The user just logged out.
 *
 * @param $account
 *   The user object on which the operation was just performed.
 */
function hook_user_logout($account) {
  db_insert('logouts')
    ->fields(array(
      'uid' => $account->id(),
      'time' => time(),
    ))
    ->execute();
}

/**
 * The user's account information is being displayed.
 *
 * The module should format its custom additions for display and add them to the
 * $build array.
 *
 * @param array &$build
 *   A renderable array representing the user content.
 * @param \Drupal\user\UserInterface $account
 *   The user object on which the operation is being performed.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the user
 *   components.
 * @param $view_mode
 *   View mode, e.g. 'full'.
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_user_view_alter()
 * @see hook_entity_view()
 */
function hook_user_view(array &$build, \Drupal\user\UserInterface $account, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode, $langcode) {
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a 'mymodule_addition' extra field has been defined for the
  // user entity type in hook_entity_extra_field_info().
  if ($display->getComponent('mymodule_addition')) {
    $build['mymodule_addition'] = array(
      '#markup' => mymodule_addition($account),
      '#theme' => 'mymodule_my_additional_field',
    );
  }
}

/**
 * The user was built; the module may modify the structured content.
 *
 * This hook is called after the content has been assembled in a structured array
 * and may be used for doing processing which requires that the complete user
 * content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the user rather than the
 * structured content array, it may use this hook to add a #post_render callback.
 * Alternatively, it could also implement hook_preprocess_HOOK() for
 * user.html.twig. See drupal_render() and _theme() documentation
 * respectively for details.
 *
 * @param array &$build
 *   A renderable array representing the user.
 * @param \Drupal\user\UserInterface $account
 *   The user account being rendered.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the user
 *   components.
 *
 * @see user_view()
 * @see hook_entity_view_alter()
 */
function hook_user_view_alter(array &$build, \Drupal\user\UserInterface $account, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display) {
  // Check for the existence of a field added by another module.
  if (isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the user.
  $build['#post_render'][] = 'my_module_user_post_render';
}

/**
 * Inform other modules that a user role is about to be saved.
 *
 * Modules implementing this hook can act on the user role object before
 * it has been saved to the database.
 *
 * @param $role
 *   A user role object.
 *
 * @see hook_user_role_insert()
 * @see hook_user_role_update()
 */
function hook_user_role_presave($role) {
  // Set a UUID for the user role if it doesn't already exist
  if (empty($role->uuid)) {
    $role->uuid = uuid_uuid();
  }
}

/**
 * Inform other modules that a user role has been added.
 *
 * Modules implementing this hook can act on the user role object when saved to
 * the database. It's recommended that you implement this hook if your module
 * adds additional data to user roles object. The module should save its custom
 * additions to the database.
 *
 * @param $role
 *   A user role object.
 */
function hook_user_role_insert($role) {
  // Save extra fields provided by the module to user roles.
  db_insert('my_module_table')
    ->fields(array(
      'rid' => $role->id(),
      'role_description' => $role->description,
    ))
    ->execute();
}

/**
 * Inform other modules that a user role has been updated.
 *
 * Modules implementing this hook can act on the user role object when updated.
 * It's recommended that you implement this hook if your module adds additional
 * data to user roles object. The module should save its custom additions to
 * the database.
 *
 * @param $role
 *   A user role object.
 */
function hook_user_role_update($role) {
  // Save extra fields provided by the module to user roles.
  db_merge('my_module_table')
    ->key('rid', $role->id())
    ->fields(array(
      'role_description' => $role->description
    ))
    ->execute();
}

/**
 * Inform other modules that a user role has been deleted.
 *
 * This hook allows you act when a user role has been deleted.
 * If your module stores references to roles, it's recommended that you
 * implement this hook and delete existing instances of the deleted role
 * in your module database tables.
 *
 * @param $role
 *   The $role object being deleted.
 */
function hook_user_role_delete($role) {
  // Delete existing instances of the deleted role.
  db_delete('my_module_table')
    ->condition('rid', $role->id())
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
