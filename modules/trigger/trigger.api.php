<?php
// $Id$

/**
 * @file
 * Hooks provided by the Trigger module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Declare information about one or more Drupal actions.
 *
 * Any module can define any number of Drupal actions. The trigger module is an
 * example of a module that uses actions. An action consists of two or three
 * parts: (1) an action definition (returned by this hook), (2) a function which
 * does the action (which by convention is named module + '_' + description of
 * what the function does + '_action'), and an optional form definition
 * function that defines a configuration form (which has the name of the action
 * with '_form' appended to it.)
 *
 * @return
 *  - An array of action descriptions. Each action description is an associative
 *    array, where the key of the item is the action's function, and the
 *    following key-value pairs:
 *     - 'type': (required) the type is determined by what object the action
 *       acts on. Possible choices are node, user, comment, and system. Or
 *       whatever your own custom type is.  So, for the nodequeue module, the
 *       type might be set to 'nodequeue' if the action would be performed on a
 *       nodequeue.
 *     - 'description': (required) The human-readable name of the action.
 *     - 'configurable': (required) If FALSE, then the action doesn't require
 *       any extra configuration.  If TRUE, then you should define a form
 *       function with the same name as the key, but with '_form' appended to
 *       it (i.e., the form for 'node_assign_owner_action' is
 *       'node_assign_owner_action_form'.)
 *       This function will take the $context as the only parameter, and is
 *       paired with the usual _submit function, and possibly a _validate
 *       function.
 *     - 'hooks': (required) An array of all of the operations this action is
 *       appropriate for, keyed by hook name.  The trigger module uses this to
 *       filter out inappropriate actions when presenting the interface for
 *       assigning actions to events.  If you are writing actions in your own
 *       modules and you simply want to declare support for all possible hooks,
 *       you can set 'hooks' => array('any' => TRUE).  Common hooks are 'user',
 *       'nodeapi', 'comment', or 'taxonomy'. Any hook that has been described
 *       to Drupal in hook_hook_info() will work is a possiblity.
 *     - 'behavior': (optional) Human-readable array of behavior descriptions.
 *       The only one we have now is 'changes node property'.  You will almost
 *       certainly never have to return this in your own implementations of this
 *       hook.
 *
 * The function that is called when the action is triggered is passed two
 * parameters - an object of the same type as the 'type' value of the
 * hook_action_info array, and a context variable that contains the context
 * under which the action is currently running, sent as an array.  For example,
 * the actions module sets the 'hook' and 'op' keys of the context array (so,
 * 'hook' may be 'nodeapi' and 'op' may be 'insert').
 */
function hook_action_info() {
  return array(
    'comment_unpublish_action' => array(
      'description' => t('Unpublish comment'),
      'type' => 'comment',
      'configurable' => FALSE,
      'hooks' => array(
        'comment' => array('insert', 'update'),
      )
    ),
    'comment_unpublish_by_keyword_action' => array(
      'description' => t('Unpublish comment containing keyword(s)'),
      'type' => 'comment',
      'configurable' => TRUE,
      'hooks' => array(
        'comment' => array('insert', 'update'),
      )
    )
  );
}

/**
 * Execute code after an action is deleted.
 *
 * @param $aid
 *   The action ID.
 */
function hook_actions_delete($aid) {
  db_query("DELETE FROM {actions_assignments} WHERE aid = '%s'", $aid);
}

/**
 * Alter the actions declared by another module.
 *
 * Called by actions_list() to allow modules to alter the return
 * values from implementations of hook_action_info().
 *
 * @see trigger_example_action_info_alter().
 */
function hook_action_info_alter(&$actions) {
  $actions['node_unpublish_action']['description'] = t('Unpublish and remove from public view.');
}

/**
 * Expose a list of triggers (events) that your module is allowing users to
 * assign actions to.
 *
 * This hook is used by the Triggers API to present information about triggers
 * (or events) that your module allows users to assign actions to.
 *
 * See also hook_action_info().
 *
 * @return
 *   - A nested array.  The outermost key defines the module that the triggers
 *     are from.  The menu system will use the key to look at the .info file of
 *     the module and make a local task (a tab) in the trigger UI.
 *     - The next key defines the hook being described.
 *       - Inside of that array are a list of arrays keyed by hook operation.
 *         - Each of those arrays have a key of 'runs when' and a value which is
 *           an English description of the hook.
 *
 * For example, the node_hook_info implementation has 'node' as the outermost
 * key, as that's the module it's in.  Next it has 'nodeapi' as the next key,
 * as hook_nodeapi() is what applies to changes in nodes.  Finally the keys
 * after that are the various operations for hook_nodeapi() that the node module
 * is exposing as triggers.
 */
function hook_hook_info() {
  return array(
    'node' => array(
      'nodeapi' => array(
        'presave' => array(
          'runs when' => t('When either saving a new post or updating an existing post'),
        ),
        'insert' => array(
          'runs when' => t('After saving a new post'),
        ),
        'update' => array(
          'runs when' => t('After saving an updated post'),
        ),
        'delete' => array(
          'runs when' => t('After deleting a post')
        ),
        'view' => array(
          'runs when' => t('When content is viewed by an authenticated user')
        ),
      ),
    ),
  );
}

/**
 * @} End of "addtogroup hooks".
 */
