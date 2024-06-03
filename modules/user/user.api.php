<?php

/**
 * @file
 * Hooks provided by the User module.
 */

/**
 * @addtogroup hooks
 * @{
 */

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
 * Respond to user deletion.
 *
 * This hook is invoked from user_delete_multiple() before field_attach_delete()
 * is called and before users are actually removed from the database.
 *
 * Modules should additionally implement hook_user_cancel() to process stored
 * user data for other account cancellation methods.
 *
 * @param $account
 *   The account that is being deleted.
 *
 * @see user_delete_multiple()
 */
function hook_user_delete($account) {
  db_delete('mytable')
    ->condition('uid', $account->uid)
    ->execute();
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
 * method. To react on this method, implement hook_user_delete() instead.
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
      $nodes = db_select('node', 'n')
        ->fields('n', array('nid'))
        ->condition('uid', $account->uid)
        ->execute()
        ->fetchCol();
      node_mass_update($nodes, array('status' => 0));
      break;

    case 'user_cancel_reassign':
      // Anonymize nodes (current revisions).
      module_load_include('inc', 'node', 'node.admin');
      $nodes = db_select('node', 'n')
        ->fields('n', array('nid'))
        ->condition('uid', $account->uid)
        ->execute()
        ->fetchCol();
      node_mass_update($nodes, array('uid' => 0));
      // Anonymize old revisions.
      db_update('node_revision')
        ->fields(array('uid' => 0))
        ->condition('uid', $account->uid)
        ->execute();
      // Clean history.
      db_delete('history')
        ->condition('uid', $account->uid)
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
 *   a method. If access is defined, the method cannot be configured as the
 *   default method.
 *
 * @param $methods
 *   An array containing user account cancellation methods, keyed by method id.
 *
 * @see user_cancel_methods()
 * @see user_cancel_confirm_form()
 */
function hook_user_cancel_methods_alter(&$methods) {
  // Limit access to disable account and unpublish content method.
  $methods['user_cancel_block_unpublish']['access'] = user_access('administer site configuration');

  // Remove the content re-assigning method.
  unset($methods['user_cancel_reassign']);

  // Add a custom zero-out method.
  $methods['mymodule_zero_out'] = array(
    'title' => t('Delete the account and remove all content.'),
    'description' => t('All your content will be replaced by empty strings.'),
    // access should be used for administrative methods only.
    'access' => user_access('access zero-out account cancellation method'),
  );
}

/**
 * Add mass user operations.
 *
 * This hook enables modules to inject custom operations into the mass operations
 * dropdown found at admin/people, by associating a callback function with
 * the operation, which is called when the form is submitted. The callback function
 * receives one initial argument, which is an array of the checked users.
 *
 * @return
 *   An array of operations. Each operation is an associative array that may
 *   contain the following key-value pairs:
 *   - "label": Required. The label for the operation, displayed in the dropdown menu.
 *   - "callback": Required. The function to call for the operation.
 *   - "callback arguments": Optional. An array of additional arguments to pass to
 *     the callback function.
 *
 */
function hook_user_operations() {
  $operations = array(
    'unblock' => array(
      'label' => t('Unblock the selected users'),
      'callback' => 'user_user_operations_unblock',
    ),
    'block' => array(
      'label' => t('Block the selected users'),
      'callback' => 'user_user_operations_block',
    ),
    'cancel' => array(
      'label' => t('Cancel the selected user accounts'),
    ),
  );
  return $operations;
}

/**
 * Define a list of user settings or profile information categories.
 *
 * There are two steps to using hook_user_categories():
 * - Create the category with hook_user_categories().
 * - Display that category on the form ID of "user_profile_form" with
 *   hook_form_FORM_ID_alter().
 *
 * Step one builds out the category but it won't be visible on your form until
 * you explicitly tell it to do so.
 *
 * The function in step two should contain the following code in order to
 * display your new category:
 * @code
 * if ($form['#user_category'] == 'mycategory') {
 *   // Return your form here.
 * }
 * @endcode
 *
 * @return
 *   An array of associative arrays. Each inner array has elements:
 *   - "name": The internal name of the category.
 *   - "title": The human-readable, localized name of the category.
 *   - "weight": An integer specifying the category's sort ordering.
 *   - "access callback": Name of the access callback function to use to
 *     determine whether the user can edit the category. Defaults to using
 *     user_edit_access(). See hook_menu() for more information on access
 *     callbacks.
 *   - "access arguments": Arguments for the access callback function. Defaults
 *     to array(1).
 */
function hook_user_categories() {
  return array(array(
    'name' => 'account',
    'title' => t('Account settings'),
    'weight' => 1,
  ));
}

/**
 * A user account is about to be created or updated.
 *
 * This hook is primarily intended for modules that want to store properties in
 * the serialized {users}.data column, which is automatically loaded whenever a
 * user account object is loaded, modules may add to $edit['data'] in order
 * to have their data serialized on save.
 *
 * @param $edit
 *   The array of form values submitted by the user. Assign values to this
 *   array to save changes in the database.
 * @param $account
 *   The user object on which the operation is performed. Values assigned in
 *   this object will not be saved in the database.
 * @param $category
 *   The active category of user information being edited.
 *
 * @see hook_user_insert()
 * @see hook_user_update()
 */
function hook_user_presave(&$edit, $account, $category) {
  // Make sure that our form value 'mymodule_foo' is stored as
  // 'mymodule_bar' in the 'data' (serialized) column.
  if (isset($edit['mymodule_foo'])) {
    $edit['data']['mymodule_bar'] = $edit['mymodule_foo'];
  }
}

/**
 * A user account was created.
 *
 * The module should save its custom additions to the user object into the
 * database.
 *
 * @param $edit
 *   The array of form values submitted by the user.
 * @param $account
 *   The user object on which the operation is being performed.
 * @param $category
 *   The active category of user information being edited.
 *
 * @see hook_user_presave()
 * @see hook_user_update()
 */
function hook_user_insert(&$edit, $account, $category) {
  db_insert('mytable')
    ->fields(array(
      'myfield' => $edit['myfield'],
      'uid' => $account->uid,
    ))
    ->execute();
}

/**
 * A user account was updated.
 *
 * Modules may use this hook to update their user data in a custom storage
 * after a user account has been updated.
 *
 * @param $edit
 *   The array of form values submitted by the user.
 * @param $account
 *   The user object on which the operation is performed.
 * @param $category
 *   The active category of user information being edited.
 *
 * @see hook_user_presave()
 * @see hook_user_insert()
 */
function hook_user_update(&$edit, $account, $category) {
  db_insert('user_changes')
    ->fields(array(
      'uid' => $account->uid,
      'changed' => time(),
    ))
    ->execute();
}

/**
 * The user just logged in.
 *
 * @param $form_state
 *   A keyed array containing the current state of the login form.
 * @param $account
 *   The user object on which the operation was just performed.
 */
function hook_user_login(&$form_state, $account) {
  // If the user has a NULL time zone, notify them to set a time zone.
  if (!$account->timezone && variable_get('configurable_timezones', 1) && variable_get('empty_timezone_message', 0)) {
    drupal_set_message(t('Configure your <a href="@user-edit">account time zone setting</a>.', array('@user-edit' => url("user/$account->uid/edit", array('query' => drupal_get_destination(), 'fragment' => 'edit-timezone')))));
  }
}

/**
 * The user just logged out.
 *
 * Note that when this hook is invoked, the changes have not yet been written to
 * the database, because a database transaction is still in progress. The
 * transaction is not finalized until the save operation is entirely completed
 * and user_save() goes out of scope. You should not rely on data in the
 * database at this time as it is not updated yet. You should also note that any
 * write/update database queries executed from this hook are also not committed
 * immediately. Check user_save() and db_transaction() for more info.
 *
 * @param $account
 *   The user object on which the operation was just performed.
 */
function hook_user_logout($account) {
  db_insert('logouts')
    ->fields(array(
      'uid' => $account->uid,
      'time' => time(),
    ))
    ->execute();
}

/**
 * The user's account information is being displayed.
 *
 * The module should format its custom additions for display and add them to the
 * $account->content array.
 *
 * @param $account
 *   The user object on which the operation is being performed.
 * @param $view_mode
 *   View mode, e.g. 'full'.
 * @param $langcode
 *   The language code used for rendering.
 *
 * @see hook_user_view_alter()
 * @see hook_entity_view()
 */
function hook_user_view($account, $view_mode, $langcode) {
  if (user_access('create blog content', $account)) {
    $account->content['summary']['blog'] =  array(
      '#type' => 'user_profile_item',
      '#title' => t('Blog'),
      '#markup' => l(t('View recent blog entries'), "blog/$account->uid", array('attributes' => array('title' => t("Read !username's latest blog entries.", array('!username' => format_username($account)))))),
      '#attributes' => array('class' => array('blog')),
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
 * Alternatively, it could also implement hook_preprocess_user_profile(). See
 * drupal_render() and theme() documentation respectively for details.
 *
 * @param $build
 *   A renderable array representing the user.
 *
 * @see user_view()
 * @see hook_entity_view_alter()
 */
function hook_user_view_alter(&$build) {
  // Check for the existence of a field added by another module.
  if (isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;
  }

  // Add a #post_render callback to act on the rendered HTML of the user.
  $build['#post_render'][] = 'my_module_user_post_render';
}

/**
 * Act on a user role being inserted or updated.
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
 * Respond to creation of a new user role.
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
      'rid' => $role->rid,
      'role_description' => $role->description,
    ))
    ->execute();
}

/**
 * Respond to updates to a user role.
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
    ->key(array('rid' => $role->rid))
    ->fields(array(
      'role_description' => $role->description
    ))
    ->execute();
}

/**
 * Respond to user role deletion.
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
    ->condition('rid', $role->rid)
    ->execute();
}

/**
 * Respond to user flood control events.
 *
 * This hook allows you act when an unsuccessful user login has triggered
 * flood control. This means that either an IP address or a specific user
 * account has been temporarily blocked from logging in.
 *
 * @param $ip
 *   The IP address that triggered flood control.
 * @param $username
 *   The username that has been temporarily blocked.
 *
 * @see user_login_final_validate()
 */
function hook_user_flood_control($ip, $username = FALSE) {
  if (!empty($username)) {
    // Do something with the blocked $username and $ip. For example, send an
    // e-mail to the user and/or site administrator.

    // Drupal core uses this hook to log the event:
    watchdog('user', 'Flood control blocked login attempt for %user from %ip.', array('%user' => $username, '%ip' => $ip));
  }
  else {
    // Do something with the blocked $ip. For example, add it to a block-list.

    // Drupal core uses this hook to log the event:
    watchdog('user', 'Flood control blocked login attempt from %ip.', array('%ip' => $ip));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
