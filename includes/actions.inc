<?php
// $Id$

/**
 * @file
 * This is the actions engine for executing stored actions.
 */

/**
 * @defgroup actions Actions
 * @{
 * Functions that perform an action on a certain system object.
 *
 * All modules should declare their action functions to be in this group and
 * each action function should reference its configuration form, validate, and
 * submit functions using \@see. Conversely, form, validate, and submit
 * functions should reference the action function using \@see. For examples of
 * this see comment_unpublish_by_keyword_action(), which has the following in
 * its doxygen documentation:
 *
 * \@ingroup actions
 * \@see comment_unpublish_by_keyword_action_form().
 * \@see comment_unpublish_by_keyword_action_submit().
 *
 * @} End of "defgroup actions".
 */

/**
 * @defgroup actions Actions
 * @{
 * Functions that perform an action on a certain system object.
 *
 * All modules should declare their action functions to be in this group and
 * each action function should reference its configuration form, validate, and
 * submit functions using \@see. Conversely, form, validate, and submit
 * functions should reference the action function using \@see. For examples of
 * this see comment_unpublish_by_keyword_action(), which has the following in
 * its doxygen documentation:
 *
 * \@ingroup actions
 * \@see comment_unpublish_by_keyword_action_form().
 * \@see comment_unpublish_by_keyword_action_submit().
 *
 * @} End of "defgroup actions".
 */

/**
 * Perform a given list of actions by executing their callback functions.
 *
 * Given the IDs of actions to perform, find out what the callbacks
 * for the actions are by querying the database. Then call each callback
 * using the function call $function($object, $context, $a1, $a2)
 * where $function is the name of a function written in compliance with
 * the action specification; that is, foo($object, $context).
 *
 * @param $action_ids
 *   The ID of the action to perform. Can be a single action ID or an array
 *   of IDs. IDs of instances will be numeric; IDs of singletons will be
 *   function names.
 * @param $object
 *   Parameter that will be passed along to the callback. Typically the
 *   object that the action will act on; a node, user or comment object.
 *   If the action does not act on an object, pass a dummy object. This
 *   is necessary to support PHP 4 object referencing.
 * @param $context
 *   Parameter that will be passed along to the callback. $context is a
 *   keyed array containing extra information about what is currently
 *   happening at the time of the call. Typically $context['hook'] and
 *   $context['op'] will tell which hook-op combination resulted in this
 *   call to actions_do().
 * @param $a1
 *   Parameter that will be passed along to the callback.
 * @param $a2
 *   Parameter that will be passed along to the callback.
 *
 * @return
 *   An associative array containing the result of the function that
 *   performs the action, keyed on action ID.
 */
function actions_do($action_ids, &$object, $context = NULL, $a1 = NULL, $a2 = NULL) {
  // $stack tracks the number of recursive calls.
  static $stack;
  $stack++;
  if ($stack > variable_get('actions_max_stack', 35)) {
    watchdog('actions', 'Stack overflow: too many calls to actions_do(). Aborting to prevent infinite recursion.', array(), WATCHDOG_ERROR);
    return;
  }
  $actions = array();
  $available_actions = actions_list();
  $result = array();
  if (is_array($action_ids)) {
    $where = array();
    $where_values = array();
    foreach ($action_ids as $action_id) {
      if (is_numeric($action_id)) {
        $where[] = "OR aid = '%s'";
        $where_values[] = $action_id;
      }
      elseif (isset($available_actions[$action_id])) {
        $actions[$action_id] = $available_actions[$action_id];
      }
    }

    // When we have action instances we must go to the database to
    // retrieve instance data.
    if ($where) {
      $where_clause = implode(' ', $where);
      // Strip off leading 'OR '.
      $where_clause = '('. strstr($where_clause, " ") .')';
      $result_db = db_query('SELECT * FROM {actions} WHERE '. $where_clause, $where_values);
      while ($action = db_fetch_object($result_db)) {
        $actions[$action->aid] = $action->parameters ? unserialize($action->parameters) : array();
        $actions[$action->aid]['callback'] = $action->callback;
        $actions[$action->aid]['type'] = $action->type;
      }
    }

    // Fire actions, in no particular order.
    foreach ($actions as $action_id => $params) {
      if (is_numeric($action_id)) { // Configurable actions need parameters.
        $function = $params['callback'];
        if (function_exists($function)) {
          $context = array_merge($context, $params);
          $actions_result[$action_id] = $function($object, $context, $a1, $a2);
        }
        else {
          $actions_result[$action_id] = FALSE;
        }
      }
      // Singleton action; $action_id is the function name.
      else {
        $result[$action_id] = $action_id($object, $context, $a1, $a2);
      }
    }
  }
  // Optimized execution of single action.
  else {
    // If it's a configurable action, retrieve stored parameters.
    if (is_numeric($action_ids)) {
      $action = db_fetch_object(db_query("SELECT * FROM {actions} WHERE aid = '%s'", $action_ids));
      $function = $action->callback;
      if (function_exists($function)) {
        $context = array_merge($context, unserialize($action->parameters));
        $actions_result[$action_ids] = $function($object, $context, $a1, $a2);
      }
      else {
        $actions_result[$action_ids] = FALSE;
      }
    }
    // Singleton action; $action_ids is the function name.
    else {
      $result[$action_ids] = $action_ids($object, $context, $a1, $a2);
    }
  }
  $stack--;
  return $result;
}


/**
 * Discover all action functions by invoking hook_action_info().
 *
 * @code
 * mymodule_action_info() {
 *   return array(
 *     'mymodule_functiondescription_action' => array(
 *       'type' => 'node',
 *       'description' => t('Save node'),
 *       'configurable' => FALSE,
 *       'hooks' => array(
 *         'nodeapi' => array('delete', 'insert', 'update', 'view'),
 *         'comment' => array('delete', 'insert', 'update', 'view'),
 *       )
 *     )
 *   );
 * }
 * @endcode
 *
 * The description is used in presenting possible actions to the user for
 * configuration. The type is used to present these actions in a logical
 * grouping and to denote context. Some types are 'node', 'user', 'comment',
 * and 'system'. If an action is configurable it will provide form,
 * validation and submission functions. The hooks the action supports
 * are declared in the 'hooks' array.
 *
 * @param $reset
 *   Reset the action info static cache.
 *
 * @return
 *   An associative array keyed on function name. The value of each key is
 *   an array containing information about the action, such as type of
 *   action and description of the action, e.g.,
 *
 *   @code
 *   $actions['node_publish_action'] = array(
 *     'type' => 'node',
 *     'description' => t('Publish post'),
 *     'configurable' => FALSE,
 *     'hooks' => array(
 *       'nodeapi' => array('presave', 'insert', 'update', 'view'),
 *       'comment' => array('delete', 'insert', 'update', 'view'),
 *     ),
 *   );
 *   @endcode
 */
function actions_list($reset = FALSE) {
  static $actions;
  if (!isset($actions) || $reset) {
    $actions = module_invoke_all('action_info');
    drupal_alter('action_info', $actions);
  }

  // See module_implements for explanations of this cast.
  return (array)$actions;
}

/**
 * Retrieves all action instances from the database.
 *
 * Compare with actions_list(), which gathers actions by invoking
 * hook_action_info(). The actions returned by this function and the actions
 * returned by actions_list() are partially synchronized. Non-configurable
 * actions from hook_action_info() implementations are put into the database
 * when actions_synchronize() is called, which happens when
 * admin/settings/actions is visited. Configurable actions are not added to
 * the database until they are configured in the user interface, in which case
 * a database row is created for each configuration of each action.
 *
 * @return
 *   Associative array keyed by action ID. Each value is an
 *   associative array with keys 'callback', 'description', 'type' and
 *   'configurable'.
 */
function actions_get_all_actions() {
  $actions = array();
  $result = db_query("SELECT * FROM {actions}");
  while ($action = db_fetch_object($result)) {
    $actions[$action->aid] = array(
      'callback' => $action->callback,
      'description' => $action->description,
      'type' => $action->type,
      'configurable' => (bool) $action->parameters,
    );
  }
  return $actions;
}

/**
 * Create an associative array keyed by md5 hashes of function names.
 *
 * Hashes are used to prevent actual function names from going out into
 * HTML forms and coming back.
 *
 * @param $actions
 *   An associative array with function names as keys and associative
 *   arrays with keys 'description', 'type', etc. as values. Generally
 *   the output of actions_list() or actions_get_all_actions() is given
 *   as input to this function.
 *
 * @return
 *   An associative array keyed on md5 hash of function name. The value of
 *   each key is an associative array of function, description, and type
 *   for the action.
 */
function actions_actions_map($actions) {
  $actions_map = array();
  foreach ($actions as $callback => $array) {
    $key = md5($callback);
    $actions_map[$key]['callback']     = isset($array['callback']) ? $array['callback'] : $callback;
    $actions_map[$key]['description']  = $array['description'];
    $actions_map[$key]['type']         = $array['type'];
    $actions_map[$key]['configurable'] = $array['configurable'];
  }
  return $actions_map;
}

/**
 * Given an md5 hash of a function name, return the function name.
 *
 * Faster than actions_actions_map() when you only need the function name.
 *
 * @param $hash
 *   MD5 hash of a function name
 *
 * @return
 *   Function name
 */
function actions_function_lookup($hash) {
  $actions_list = actions_list();
  foreach ($actions_list as $function => $array) {
    if (md5($function) == $hash) {
      return $function;
    }
  }

  // Must be an instance; must check database.
  $aid = db_result(db_query("SELECT aid FROM {actions} WHERE MD5(aid) = '%s' AND parameters <> ''", $hash));
  return $aid;
}

/**
 * Synchronize actions that are provided by modules.
 *
 * They are synchronized with actions that are stored in the actions table.
 * This is necessary so that actions that do not require configuration can
 * receive action IDs. This is not necessarily the best approach,
 * but it is the most straightforward.
 */
function actions_synchronize($actions_in_code = array(), $delete_orphans = FALSE) {
  if (!$actions_in_code) {
    $actions_in_code = actions_list(TRUE);
  }
  $actions_in_db = array();
  $result = db_query("SELECT * FROM {actions} WHERE parameters = ''");
  while ($action = db_fetch_object($result)) {
    $actions_in_db[$action->callback] = array('aid' => $action->aid, 'description' => $action->description);
  }

  // Go through all the actions provided by modules.
  foreach ($actions_in_code as $callback => $array) {
    // Ignore configurable actions since their instances get put in
    // when the user adds the action.
    if (!$array['configurable']) {
      // If we already have an action ID for this action, no need to assign aid.
      if (array_key_exists($callback, $actions_in_db)) {
        unset($actions_in_db[$callback]);
      }
      else {
        // This is a new singleton that we don't have an aid for; assign one.
        db_query("INSERT INTO {actions} (aid, type, callback, parameters, description) VALUES ('%s', '%s', '%s', '%s', '%s')", $callback, $array['type'], $callback, '', $array['description']);
        watchdog('actions', "Action '%action' added.", array('%action' => $array['description']));
      }
    }
  }

  // Any actions that we have left in $actions_in_db are orphaned.
  if ($actions_in_db) {
    $orphaned = array();
    $placeholder = array();

    foreach ($actions_in_db as $callback => $array) {
      $orphaned[] = $callback;
      $placeholder[] = "'%s'";
    }

    $orphans = implode(', ', $orphaned);

    if ($delete_orphans) {
      $placeholders = implode(', ', $placeholder);
      $results = db_query("SELECT a.aid, a.description FROM {actions} a WHERE callback IN ($placeholders)", $orphaned);
      while ($action = db_fetch_object($results)) {
        actions_delete($action->aid);
        watchdog('actions', "Removed orphaned action '%action' from database.", array('%action' => $action->description));
      }
    }
    else {
      $link = l(t('Remove orphaned actions'), 'admin/settings/actions/orphan');
      $count = count($actions_in_db);
      watchdog('actions', format_plural($count, 'One orphaned action (%orphans) exists in the actions table. !link', '@count orphaned actions (%orphans) exist in the actions table. !link'), array('@count' => $count, '%orphans' => $orphans, '!link' => $link), WATCHDOG_WARNING);
    }
  }
}

/**
 * Save an action and its associated user-supplied parameter values to the database.
 *
 * @param $function
 *   The name of the function to be called when this action is performed.
 * @param $type
 *   The type of action, to describe grouping and/or context, e.g., 'node',
 *   'user', 'comment', or 'system'.
 * @param $params
 *   An associative array with parameter names as keys and parameter values
 *   as values.
 * @param $desc
 *   A user-supplied description of this particular action, e.g., 'Send
 *   e-mail to Jim'.
 * @param $aid
 *   The ID of this action. If omitted, a new action is created.
 *
 * @return
 *   The ID of the action.
 */
function actions_save($function, $type, $params, $desc, $aid = NULL) {
  $serialized = serialize($params);
  if ($aid) {
    db_query("UPDATE {actions} SET callback = '%s', type = '%s', parameters = '%s', description = '%s' WHERE aid = '%s'", $function, $type, $serialized, $desc, $aid);
    watchdog('actions', 'Action %action saved.', array('%action' => $desc));
  }
  else {
    // aid is the callback for singleton actions so we need to keep a
    // separate table for numeric aids.
    db_query('INSERT INTO {actions_aid} VALUES (default)');
    $aid = db_last_insert_id('actions_aid', 'aid');
    db_query("INSERT INTO {actions} (aid, callback, type, parameters, description) VALUES ('%s', '%s', '%s', '%s', '%s')", $aid, $function, $type, $serialized, $desc);
    watchdog('actions', 'Action %action created.', array('%action' => $desc));
  }

  return $aid;
}

/**
 * Retrieve a single action from the database.
 *
 * @param $aid
 *   integer The ID of the action to retrieve.
 *
 * @return
 *   The appropriate action row from the database as an object.
 */
function actions_load($aid) {
  return db_fetch_object(db_query("SELECT * FROM {actions} WHERE aid = '%s'", $aid));
}

/**
 * Delete a single action from the database.
 *
 * @param $aid
 *   integer The ID of the action to delete.
 */
function actions_delete($aid) {
  db_query("DELETE FROM {actions} WHERE aid = '%s'", $aid);
  module_invoke_all('actions_delete', $aid);
}
