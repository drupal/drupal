<?php
// $Id$

/**
 * @file
 * Hooks provided by the User module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on user account actions.
 *
 * This hook allows modules to react when operations are performed on user
 * accounts.
 *
 * @param $op
 *   What kind of action is being performed. Possible values (in alphabetical order):
 *   - "after_update": The user object has been updated and changed. Use this if
 *     (probably along with 'insert') if you want to reuse some information from
 *     the user object.
 *   - "categories": A set of user information categories is requested.
 *   - "delete": The user account is being deleted. The module should remove its
 *     custom additions to the user object from the database.
 *   - "form": The user account edit form is about to be displayed. The module
 *     should present the form elements it wishes to inject into the form.
 *   - "insert": The user account is being added. The module should save its
 *     custom additions to the user object into the database and set the saved
 *     fields to NULL in $edit.
 *   - "load": The user account is being loaded. The module may respond to this
 *   - "login": The user just logged in.
 *   - "logout": The user just logged out.
 *     and insert additional information into the user object.
 *   - "register": The user account registration form is about to be displayed.
 *     The module should present the form elements it wishes to inject into the
 *     form.
 *   - "submit": Modify the account before it gets saved.
 *   - "update": The user account is being changed. The module should save its
 *     custom additions to the user object into the database and set the saved
 *     fields to NULL in $edit.
 *   - "validate": The user account is about to be modified. The module should
 *     validate its custom additions to the user object, registering errors as
 *     necessary.
 *   - "view": The user's account information is being displayed. The module
 *     should format its custom additions for display and add them to the
 *     $account->content array.
 * @param &$edit
 *   The array of form values submitted by the user.
 * @param &$account
 *   The user object on which the operation is being performed.
 * @param $category
 *   The active category of user information being edited.
 * @return
 *   This varies depending on the operation.
 *   - "categories": A linear array of associative arrays. These arrays have
 *     keys:
 *     - "name": The internal name of the category.
 *     - "title": The human-readable, localized name of the category.
 *     - "weight": An integer specifying the category's sort ordering.
 *   - "delete": None.
 *   - "form", "register": A $form array containing the form elements to display.
 *   - "insert": None.
 *   - "load": None.
 *   - "login": None.
 *   - "logout": None.
 *   - "submit": None:
 *   - "update": None.
 *   - "validate": None.
 *   - "view": None. For an example see: user_user().
 */
function hook_user($op, &$edit, &$account, $category = NULL) {
  if ($op == 'form' && $category == 'account') {
    $form['comment_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Comment settings'),
      '#collapsible' => TRUE,
      '#weight' => 4);
    $form['comment_settings']['signature'] = array(
      '#type' => 'textarea',
      '#title' => t('Signature'),
      '#default_value' => $edit['signature'],
      '#description' => t('Your signature will be publicly displayed at the end of your comments.'));
    return $form;
  }
}

/**
 * Add mass user operations.
 *
 * This hook enables modules to inject custom operations into the mass operations
 * dropdown found at admin/user/user, by associating a callback function with
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
    'delete' => array(
      'label' => t('Delete the selected users'),
    ),
  );
  return $operations;
}

/**
 * @} End of "addtogroup hooks".
 */
