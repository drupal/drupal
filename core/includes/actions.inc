<?php

/**
 * @file
 * This is the actions engine for executing stored actions.
 */

/**
 * @defgroup actions Actions
 * @{
 * Functions that perform an action on a certain system object.
 *
 * Action functions are declared by modules by implementing hook_action_info().
 * Modules can cause action functions to run by calling actions_do(), and
 * trigger.module provides a user interface that lets administrators define
 * events that cause action functions to run.
 *
 * Each action function takes two to four arguments:
 * - $entity: The object that the action acts on, such as a node, comment, or
 *   user.
 * - $context: Array of additional information about what triggered the action.
 * - $a1, $a2: Optional additional information, which can be passed into
 *   actions_do() and will be passed along to the action function.
 *
 * @}
 */

/**
 * Performs a given list of actions by executing their callback functions.
 *
 * Given the IDs of actions to perform, this function finds out what the
 * callback functions for the actions are by querying the database. Then
 * it calls each callback using the function call $function($object, $context,
 * $a1, $a2), passing the input arguments of this function (see below) to the
 * action function.
 *
 * @param $action_ids
 *   The IDs of the actions to perform. Can be a single action ID or an array
 *   of IDs. IDs of configurable actions must be given as numeric action IDs;
 *   IDs of non-configurable actions may be given as action function names.
 * @param $object
 *   The object that the action will act on: a node, user, or comment object.
 * @param $context
 *   Associative array containing extra information about what triggered
 *   the action call, with $context['hook'] giving the name of the hook
 *   that resulted in this call to actions_do().
 * @param $a1
 *   Passed along to the callback.
 * @param $a2
 *   Passed along to the callback.
 *
 * @return
 *   An associative array containing the results of the functions that
 *   perform the actions, keyed on action ID.
 *
 * @ingroup actions
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
      if (function_exists($action_ids)) {
        $actions_result[$action_ids] = $action_ids($object, $context, $a1, $a2);
      }
      else {
        // Set to avoid undefined index error messages later.
        $actions_result[$action_ids] = FALSE;
      }
    }
  }
  $stack--;
  return $actions_result;
}

/**
 * Discovers all available actions by invoking hook_action_info().
 *
 * This function contrasts with actions_get_all_actions(); see the
 * documentation of actions_get_all_actions() for an explanation.
 *
 * @param $reset
 *   Reset the action info static cache.
 *
 * @return
 *   An associative array keyed on action function name, with the same format
 *   as the return value of hook_action_info(), containing all
 *   modules' hook_action_info() return values as modified by any
 *   hook_action_info_alter() implementations.
 *
 * @see hook_action_info()
 */
function actions_list($reset = FALSE) {
  $actions = &drupal_static(__FUNCTION__);
  if (!isset($actions) || $reset) {
    $actions = module_invoke_all('action_info');
    drupal_alter('action_info', $actions);
  }

  // See module_implements() for an explanation of this cast.
  return (array) $actions;
}

/**
 * Retrieves all action instances from the database.
 *
 * This function differs from the actions_list() function, which gathers
 * actions by invoking hook_action_info(). The actions returned by this
 * function and the actions returned by actions_list() are partially
 * synchronized. Non-configurable actions from hook_action_info()
 * implementations are put into the database when actions_synchronize() is
 * called, which happens when admin/config/system/actions is visited.
 * Configurable actions are not added to the database until they are configured
 * in the user interface, in which case a database row is created for each
 * configuration of each action.
 *
 * @return
 *   Associative array keyed by numeric action ID. Each value is an associative
 *   array with keys 'callback', 'label', 'type' and 'configurable'.
 */
function actions_get_all_actions() {
  $actions = db_query("SELECT aid, type, callback, parameters, label FROM {actions}")->fetchAllAssoc('aid', PDO::FETCH_ASSOC);
  foreach ($actions as &$action) {
    $action['configurable'] = (bool) $action['parameters'];
    unset($action['parameters']);
    unset($action['aid']);
  }
  return $actions;
}

/**
 * Creates an associative array keyed by hashes of function names or IDs.
 *
 * Hashes are used to prevent actual function names from going out into HTML
 * forms and coming back.
 *
 * @param $actions
 *   An associative array with function names or action IDs as keys
 *   and associative arrays with keys 'label', 'type', etc. as values.
 *   This is usually the output of actions_list() or actions_get_all_actions().
 *
 * @return
 *   An associative array whose keys are hashes of the input array keys, and
 *   whose corresponding values are associative arrays with components
 *   'callback', 'label', 'type', and 'configurable' from the input array.
 */
function actions_actions_map($actions) {
  $actions_map = array();
  foreach ($actions as $callback => $array) {
    $key = drupal_hash_base64($callback);
    $actions_map[$key]['callback']     = isset($array['callback']) ? $array['callback'] : $callback;
    $actions_map[$key]['label']        = $array['label'];
    $actions_map[$key]['type']         = $array['type'];
    $actions_map[$key]['configurable'] = $array['configurable'];
  }
  return $actions_map;
}

/**
 * Returns an action array key (function or ID), given its hash.
 *
 * Faster than actions_actions_map() when you only need the function name or ID.
 *
 * @param $hash
 *   Hash of a function name or action ID array key. The array key
 *   is a key into the return value of actions_list() (array key is the action
 *   function name) or actions_get_all_actions() (array key is the action ID).
 *
 * @return
 *   The corresponding array key, or FALSE if no match is found.
 */
function actions_function_lookup($hash) {
  // Check for a function name match.
  $actions_list = actions_list();
  foreach ($actions_list as $function => $array) {
    if (drupal_hash_base64($function) == $hash) {
      return $function;
    }
  }
  $aid = FALSE;
  // Must be a configurable action; check database.
  $result = db_query("SELECT aid FROM {actions} WHERE parameters <> ''")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($result as $row) {
    if (drupal_hash_base64($row['aid']) == $hash) {
      $aid = $row['aid'];
      break;
    }
  }
  return $aid;
}

/**
 * Synchronizes actions that are provided by modules in hook_action_info().
 *
 * Actions provided by modules in hook_action_info() implementations are
 * synchronized with actions that are stored in the actions database table.
 * This is necessary so that actions that do not require configuration can
 * receive action IDs.
 *
 * @param $delete_orphans
 *   If TRUE, any actions that exist in the database but are no longer
 *   found in the code (for example, because the module that provides them has
 *   been disabled) will be deleted.
 */
function actions_synchronize($delete_orphans = FALSE) {
  $actions_in_code = actions_list(TRUE);
  $actions_in_db = db_query("SELECT aid, callback, label FROM {actions} WHERE parameters = ''")->fetchAllAssoc('callback', PDO::FETCH_ASSOC);

  // Go through all the actions provided by modules.
  foreach ($actions_in_code as $callback => $array) {
    // Ignore configurable actions since their instances get put in when the
    // user adds the action.
    if (!$array['configurable']) {
      // If we already have an action ID for this action, no need to assign aid.
      if (isset($actions_in_db[$callback])) {
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
            'label' => $array['label'],
            ))
          ->execute();
        watchdog('actions', "Action '%action' added.", array('%action' => $array['label']));
      }
    }
  }

  // Any actions that we have left in $actions_in_db are orphaned.
  if ($actions_in_db) {
    $orphaned = array_keys($actions_in_db);

    if ($delete_orphans) {
      $actions = db_query('SELECT aid, label FROM {actions} WHERE callback IN (:orphaned)', array(':orphaned' => $orphaned))->fetchAll();
      foreach ($actions as $action) {
        actions_delete($action->aid);
        watchdog('actions', "Removed orphaned action '%action' from database.", array('%action' => $action->label));
      }
    }
    else {
      $link = l(t('Remove orphaned actions'), 'admin/config/system/actions/orphan');
      $count = count($actions_in_db);
      $orphans = implode(', ', $orphaned);
      watchdog('actions', '@count orphaned actions (%orphans) exist in the actions table. !link', array('@count' => $count, '%orphans' => $orphans, '!link' => $link), WATCHDOG_INFO);
    }
  }
}

/**
 * Saves an action and its user-supplied parameter values to the database.
 *
 * @param $function
 *   The name of the function to be called when this action is performed.
 * @param $type
 *   The type of action, to describe grouping and/or context, e.g., 'node',
 *   'user', 'comment', or 'system'.
 * @param $params
 *   An associative array with parameter names as keys and parameter values as
 *   values.
 * @param $label
 *   A user-supplied label of this particular action, e.g., 'Send e-mail
 *   to Jim'.
 * @param $aid
 *   The ID of this action. If omitted, a new action is created.
 *
 * @return
 *   The ID of the action.
 */
function actions_save($function, $type, $params, $label, $aid = NULL) {
  // aid is the callback for singleton actions so we need to keep a separate
  // table for numeric aids.
  if (!$aid) {
    $aid = db_next_id();
  }

  db_merge('actions')
    ->key(array('aid' => $aid))
    ->fields(array(
      'callback' => $function,
      'type' => $type,
      'parameters' => serialize($params),
      'label' => $label,
    ))
    ->execute();

  watchdog('actions', 'Action %action saved.', array('%action' => $label));
  return $aid;
}

/**
 * Retrieves a single action from the database.
 *
 * @param $aid
 *   The ID of the action to retrieve.
 *
 * @return
 *   The appropriate action row from the database as an object.
 */
function actions_load($aid) {
  return db_query("SELECT aid, type, callback, parameters, label FROM {actions} WHERE aid = :aid", array(':aid' => $aid))->fetchObject();
}

/**
 * Deletes a single action from the database.
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
