<?php
// $Id$

/**
 * @file
 * This is the actions engine for executing stored actions.
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
function actions_do($action_ids, $object = NULL, $context = NULL, $a1 = NULL, $a2 = NULL) {
  // $stack tracks the number of recursive calls.
  static $stack;
  $stack++;
  if ($stack > variable_get('actions_max_stack', 35)) {
    watchdog('actions', 'Stack overflow: too many calls to actions_do(). Aborting to prevent infinite recursion.', array(), WATCHDOG_ERROR);
    return;
  }
  $actions = array();
  $available_actions = actions_list();
  $actions_result = array();
  if (is_array($action_ids)) {
    $conditions = array();
    foreach ($action_ids as $action_id) {
      if (is_numeric($action_id)) {
        $conditions[] = $action_id;
      }
      elseif (isset($available_actions[$action_id])) {
        $actions[$action_id] = $available_actions[$action_id];
      }
    }

    // When we have action instances we must go to the database to retrieve
    // instance data.
    if (!empty($conditions)) {
      $query = db_select('actions');
      $query->addField('actions', 'aid');
      $query->addField('actions', 'type');
      $query->addField('actions', 'callback');
      $query->addField('actions', 'parameters');
      $query->condition('aid', $conditions, 'IN');
      $result = $query->execute();
      foreach ($result as $action) {
        $actions[$action->aid] = $action->parameters ? unserialize($action->parameters) : array();
        $actions[$action->aid]['callback'] = $action->callback;
        $actions[$action->aid]['type'] = $action->type;
      }
    }

    // Fire actions, in no particular order.
    foreach ($actions as $action_id => $params) {
      // Configurable actions need parameters.
      if (is_numeric($action_id)) {
        $function = $params['callback'];
        $context = array_merge($context, $params);
        $actions_result[$action_id] = $function($object, $context, $a1, $a2);
      }
      // Singleton action; $action_id is the function name.
      else {
        $actions_result[$action_id] = $action_id($object, $context, $a1, $a2);
      }
    }
  }
  // Optimized execution of a single action.
  else {
    // If it's a configurable action, retrieve stored parameters.
    if (is_numeric($action_ids)) {
      $action = db_query("SELECT callback, parameters FROM {actions} WHERE aid = :aid", array(':aid' => $action_ids))->fetchObject();
      $function = $action->callback;
      $context = array_merge($context, unserialize($action->parameters));
      $actions_result[$action_ids] = $function($object, $context, $a1, $a2);
    }
    // Singleton action; $action_ids is the function name.
    else {
      $actions_result[$action_ids] = $action_ids($object, $context, $a1, $a2);
    }
  }
  $stack--;
  return $actions_result;
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
 *         'node' => array('delete', 'insert', 'update', 'view'),
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
 *   action and description of the action, e.g.:
 *   @code
 *   $actions['node_publish_action'] = array(
 *     'type' => 'node',
 *     'description' => t('Publish post'),
 *     'configurable' => FALSE,
 *     'hooks' => array(
 *       'node' => array('presave', 'insert', 'update', 'view'),
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

  // See module_implements() for an explanation of this cast.
  return (array)$actions;
}

/**
 * Retrieve all action instances from the database.
 *
 * Compare with actions_list() which gathers actions by invoking
 * hook_action_info(). The two are synchronized by visiting
 * /admin/structure/actions (when actions.module is enabled) which runs
 * actions_synchronize().
 *
 * @return
 *   Associative array keyed by action ID. Each value is an associative array
 *   with keys 'callback', 'description', 'type' and 'configurable'.
 */
function actions_get_all_actions() {
  $actions = db_query("SELECT aid, type, callback, parameters, description FROM {actions}")->fetchAllAssoc('aid', PDO::FETCH_ASSOC);
  foreach ($actions as &$action) {
    $action['configurable'] = (bool) $action['parameters'];
    unset($action['parameters']);
    unset($action['aid']);
  }
  return $actions;
}

/**
 * Create an associative array keyed by md5 hashes of function names.
 *
 * Hashes are used to prevent actual function names from going out into HTML
 * forms and coming back.
 *
 * @param $actions
 *   An associative array with function names as keys and associative arrays
 *   with keys 'description', 'type', etc. as values. Generally the output of
 *   actions_list() or actions_get_all_actions() is given as input to this
 *   function.
 *
 * @return
 *   An associative array keyed on md5 hash of function names. The value of
 *   each key is an associative array of function, description, and type for
 *   the action.
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
 *   MD5 hash of a function name.
 *
 * @return
 *   The corresponding function name or FALSE if none is found.
 */
function actions_function_lookup($hash) {
  $actions_list = actions_list();
  foreach ($actions_list as $function => $array) {
    if (md5($function) == $hash) {
      return $function;
    }
  }

  // Must be an instance; must check database.
  return db_query("SELECT aid FROM {actions} WHERE MD5(aid) = :hash AND parameters <> ''", array(':hash' => $hash))->fetchField();
}

/**
 * Synchronize actions that are provided by modules.
 *
 * Actions provided by modules are synchronized with actions that are stored in
 * the actions table. This is necessary so that actions that do not require
 * configuration can receive action IDs. This is not necessarily the best
 * approach, but it is the most straightforward.
 *
 * @param $delete_orphans
 *   Boolean if TRUE, any actions that exist in the database but are no longer
 *   found in the code (for example, because the module that provides them has
 *   been disabled) will be deleted.
 */
function actions_synchronize($delete_orphans = FALSE) {
  $actions_in_code = actions_list(TRUE);
  $actions_in_db = db_query("SELECT aid, callback, description FROM {actions} WHERE parameters = ''")->fetchAllAssoc('callback', PDO::FETCH_ASSOC);

  // Go through all the actions provided by modules.
  foreach ($actions_in_code as $callback => $array) {
    // Ignore configurable actions since their instances get put in when the
    // user adds the action.
    if (!$array['configurable']) {
      // If we already have an action ID for this action, no need to assign aid.
      if (array_key_exists($callback, $actions_in_db)) {
        unset($actions_in_db[$callback]);
      }
      else {
        // This is a new singleton that we don't have an aid for; assign one.
        db_insert('actions')
          ->fields(array(
            'aid' => $callback,
            'type' => $array['type'],
            'callback' => $callback,
            'parameters' => '',
            'description' => $array['description'],
            ))
          ->execute();
        watchdog('actions', "Action '%action' added.", array('%action' => filter_xss_admin($array['description'])));
      }
    }
  }

  // Any actions that we have left in $actions_in_db are orphaned.
  if ($actions_in_db) {
    $orphaned = array_keys($actions_in_db);

    if ($delete_orphans) {
      $actions = db_query('SELECT aid, description FROM {actions} WHERE callback IN (:orphaned)', array(':orphaned' => $orphaned))->fetchAll();
      foreach ($actions as $action) {
        actions_delete($action->aid);
        watchdog('actions', "Removed orphaned action '%action' from database.", array('%action' => filter_xss_admin($action->description)));
      }
    }
    else {
      $link = l(t('Remove orphaned actions'), 'admin/settings/actions/orphan');
      $count = count($actions_in_db);
      $orphans = implode(', ', $orphaned);
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
 *   An associative array with parameter names as keys and parameter values as
 *   values.
 * @param $desc
 *   A user-supplied description of this particular action, e.g., 'Send e-mail
 *   to Jim'.
 * @param $aid
 *   The ID of this action. If omitted, a new action is created.
 *
 * @return
 *   The ID of the action.
 */
function actions_save($function, $type, $params, $desc, $aid = NULL) {
  // aid is the callback for singleton actions so we need to keep a separate
  // table for numeric aids.
  if (!$aid) {
    $aid = db_insert('actions_aid')->useDefaults(array('aid'))->execute();
  }

  db_merge('actions')
    ->key(array('aid' => $aid))
    ->fields(array(
      'callback' => $function,
      'type' => $type,
      'parameters' => serialize($params),
      'description' => $desc,
    ))
    ->execute();

  watchdog('actions', 'Action %action saved.', array('%action' => $desc));
  return $aid;
}

/**
 * Retrieve a single action from the database.
 *
 * @param $aid
 *   The ID of the action to retrieve.
 *
 * @return
 *   The appropriate action row from the database as an object.
 */
function actions_load($aid) {
  return db_query("SELECT aid, type, callback, parameters, description FROM {actions} WHERE aid = :aid", array(':aid' => $aid))->fetchObject();
}

/**
 * Delete a single action from the database.
 *
 * @param $aid
 *   The ID of the action to delete.
 */
function actions_delete($aid) {
  db_delete('actions')
    ->condition('aid', $aid)
    ->execute();
  module_invoke_all('actions_delete', $aid);
}

