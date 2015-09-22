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
 * Act on user account cancellations.
 *
 * This hook is invoked from user_cancel() before a user account is canceled.
 * Depending on the account cancellation method, the module should either do
 * nothing, unpublish content, or anonymize content. See user_cancel_methods()
 * for the list of default account cancellation methods provided by User module.
 * Modules may add further methods via hook_user_cancel_methods_alter().
 *
 * This hook is NOT invoked for the 'user_cancel_delete' account cancellation
 * method. To react to that method, implement hook_ENTITY_TYPE_predelete() or
 * hook_ENTITY_TYPE_delete() for user entities instead.
 *
 * Expensive operations should be added to the global account cancellation batch
 * by using batch_set().
 *
 * @param array $edit
 *   The array of form values submitted by the user.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user object on which the operation is being performed.
 * @param string $method
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
 * @param array $methods
 *   An array containing user account cancellation methods, keyed by method id.
 *
 * @see user_cancel_methods()
 * @see \Drupal\user\Form\UserCancelForm
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
 * @param string $name
 *   The string that user_format_name() will return.
 *
 * @param object $account
 *   The account object passed to user_format_name().
 *
 * @see user_format_name()
 */
function hook_user_format_name_alter(&$name, $account) {
  // Display the user's uid instead of name.
  if ($account->id()) {
    $name = t('User @uid', array('@uid' => $account->id()));
  }
}

/**
 * The user just logged in.
 *
 * @param object $account
 *   The user object on which the operation was just performed.
 */
function hook_user_login($account) {
  $config = \Drupal::config('system.date');
  // If the user has a NULL time zone, notify them to set a time zone.
  if (!$account->getTimezone() && $config->get('timezone.user.configurable') && $config->get('timezone.user.warn')) {
    drupal_set_message(t('Configure your <a href=":user-edit">account time zone setting</a>.', array(':user-edit' => $account->url('edit-form', array('query' => \Drupal::destination()->getAsArray(), 'fragment' => 'edit-timezone')))));
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
 * @} End of "addtogroup hooks".
 */
