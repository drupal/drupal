<?php

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

// Menu callback that shows menu item.
function devel_menu_item() {
  $item = menu_get_item($_GET['path']);
  return kdevel_print_object($item);
}

/**
 * Returns a list of all currently defined user functions in the current
 * request lifecycle, with links their documentation.
 */
function devel_function_reference() {
  $functions = get_defined_functions();
  $version = devel_get_core_version(VERSION);
  $ufunctions = $functions['user'];
  sort($ufunctions);
  $api = config('devel.settings')->get('api_url');
  foreach ($ufunctions as $function) {
    $links[] = l($function, "http://$api/api/$version/function/$function");
  }
  return theme('item_list', array('items' => $links));
}

/**
 * Menu callback; clears all caches, then redirects to the previous page.
 */
function devel_cache_clear() {
  drupal_flush_all_caches();

  drupal_set_message('Cache cleared.');

  drupal_goto();
}

// A menu callback. Called by the AJAX link in query log.
function devel_querylog_explain($request_id = NULL, $qid = NULL) {
  if (!is_numeric($request_id)) {
    throw new AccessDeniedHttpException();
  }

  $path = "temporary://devel_querylog/$request_id.txt";
  $path = file_stream_wrapper_uri_normalize($path);
  $queries = json_decode(file_get_contents($path));
  $query = $queries[$qid];
  $result = db_query('EXPLAIN ' . $query->query, (array)$query->args)->fetchAllAssoc('table');
  $i = 1;
  foreach ($result as $row) {
    $row = (array)$row;
    if ($i == 1) {
      $header = array_keys($row);
    }
    $rows[] = array_values($row);
    $i++;
  }
  $output = theme('table', array('header' => $header, 'rows' => $rows));

  $GLOBALS['devel_shutdown'] = FALSE;
  return new Response($output);
}

// A menu callback. Called by the AJAX link in query log.
function devel_querylog_arguments($request_id = NULL, $qid = NULL) {
  if (!is_numeric($request_id)) {
    throw new AccessDeniedHttpException();
  }

  $path = "temporary://devel_querylog/$request_id.txt";
  $path = file_stream_wrapper_uri_normalize($path);
  $queries = json_decode(file_get_contents($path));
  $query = $queries[$qid];
  $conn = Drupal\Core\Database\Database::getConnection();
  $quoted = array();
  foreach ((array)$query->args as $key => $val) {
    $quoted[$key] = $conn->quote($val);
  }
  $output = strtr($query->query, $quoted);

  $GLOBALS['devel_shutdown'] = FALSE;
  return new Response($output);
}

/**
 * Menu callback; clear the database, resetting the menu to factory defaults.
 */
function devel_menu_rebuild() {
  menu_router_rebuild();
  drupal_set_message(t('The menu router has been rebuilt.'));
  drupal_goto();
}

/**
 * Display a dropdown of installed modules with the option to reinstall them.
 */
function devel_reinstall($form, &$form_state) {
  $output = '';
  $modules = module_list();
  sort($modules);
  $options = drupal_map_assoc($modules);
  $form['list'] = array(
    '#type' => 'checkboxes',
    '#options' => $options,
    '#description' => t('Uninstall and then install the selected modules. <code>hook_uninstall()</code> and <code>hook_install()</code> will be executed and the schema version number will be set to the most recent update number. You may have to manually clear out any existing tables first if the module doesn\'t implement <code>hook_uninstall()</code>.'),
  );
  $form['submit'] = array(
    '#value' => t('Reinstall'),
    '#type' => 'submit',
  );
  return $form;
}

/**
 * Process reinstall menu form submissions.
 */
function devel_reinstall_submit($form, &$form_state) {
  // require_once './core/includes/install.inc';
  $modules = array_filter($form_state['values']['list']);
  module_disable($modules, FALSE);
  module_uninstall($modules, FALSE);
  module_enable($modules, FALSE);
  drupal_set_message(t('Uninstalled and installed: %names.', array('%names' => implode(', ', $modules))));
}

// Menu callback.
function devel_theme_registry() {
  drupal_theme_initialize();
  $hooks = theme_get_registry();
  ksort($hooks);
  return kprint_r($hooks, TRUE);
}

// Menu callback. $entity_type argument not currently used in the UI.
function devel_entity_info_page($entity_type = NULL) {
  $info = entity_get_info($entity_type);
  ksort($info);
  return kprint_r($info, TRUE);
}

// Menu callback.
function devel_field_info_page() {
  $info = field_info_fields();
  $output = kprint_r($info, TRUE, t('Fields'));
  $info = field_info_instances();
  $output .= kprint_r($info, TRUE, t('Instances'));
  $info = entity_get_bundles();
  $output .= kprint_r($info, TRUE, t('Bundles'));
  $info = field_info_field_types();
  $output .= kprint_r($info, TRUE, t('Field types'));
  $info = field_info_formatter_types();
  $output .= kprint_r($info, TRUE, t('Formatter types'));
  $info = field_info_storage_types();
  $output .= kprint_r($info, TRUE, t('Storage types'));
  $info = field_info_widget_types();
  $output .= kprint_r($info, TRUE, t('Widget types'));
  return $output;
}

/**
 * Form API form builder.
 */
function devel_variable_form($form, &$form_state) {
  $header = array(
    'name' => array('data' => t('Name'), 'field' => 'name', 'sort' => 'asc'),
    'value' => array('data' => t('Value'), 'field' => 'value'),
    'length' => array('data' => t('Length'), 'field' => 'length'),
    'edit' => array('data' => t('Operations')),
  );

  $form['variables'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#empty' => t('No variables.'),
    '#tableselect' => TRUE,
  );

  // TODO: we could get variables out of $conf but that would include hard coded ones too. ideally i would highlight overrridden/hard coded variables
  $query = db_select('variable', 'v')->extend('Drupal\Core\Database\Query\TableSortExtender');
  $query->fields('v', array('name', 'value'));
  switch (db_driver()) {
    case 'mssql':
      $query->addExpression("LEN(v.value)", 'length');
      break;
    default:
      $query->addExpression("LENGTH(v.value)", 'length');
      break;
  }
  $result = $query
    ->orderByHeader($header)
    ->execute();

  foreach ($result as $row) {
    // $variables[$row->name] = '';
    $form['variables'][$row->name]['name'] = array('#markup' => check_plain($row->name));
    if (merits_krumo($row->value)) {
      $value = krumo_ob(variable_get($row->name, NULL));
    }
    else {
      if (drupal_strlen($row->value) > 70) {
        $value = check_plain(drupal_substr($row->value, 0, 65)) .'...';
      }
      else {
        $value = check_plain($row->value);
      }
    }
    $form['variables'][$row->name]['value'] = array('#markup' => $value);
    $form['variables'][$row->name]['length'] = array('#markup' => $row->length);
    $form['variables'][$row->name]['edit'] = array('#markup' => l(t('Edit'), "devel/variable/edit/$row->name"));
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
    '#tableselect' => TRUE,
  );

    // krumo($form);
  return $form;
}

function devel_variable_form_submit($form, &$form_state) {
  $deletes = array_filter($form_state['values']['variables']);
  array_walk($deletes, 'variable_del');
  if (count($deletes)) {
    drupal_set_message(format_plural(count($deletes), 'One variable deleted.', '@count variables deleted.'));
  }
}

function devel_variable_edit($form, &$form_state, $name) {
  $value = variable_get($name, 'not found');
  $form['name'] = array(
    '#type' => 'value',
    '#value' => $name
  );
  $form['value'] = array(
    '#type' => 'item',
    '#title' => t('Old value'),
    '#markup' => dpr($value, TRUE),
  );
  if (is_string($value) || is_numeric($value)) {
    $form['new'] = array(
      '#type' => 'textarea',
      '#title' => t('New value'),
      '#default_value' => $value
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
  }
  else {
    $api = config('devel.settings')->get('api_url', 'api.drupal.org');
    $form['new'] = array(
      '#type' => 'item',
      '#title' => t('New value'),
      '#markup' => t('Sorry, complex variable types may not be edited yet. Use the <em>Execute PHP</em> block and the <a href="@variable-set-doc">variable_set()</a> function.', array('@variable-set-doc' => "http://$api/api/HEAD/function/variable_set"))
    );
  }
  drupal_set_title($name);
  return $form;
}

function devel_variable_edit_submit($form, &$form_state) {
  variable_set($form_state['values']['name'], $form_state['values']['new']);
  drupal_set_message(t('Saved new value for %name.', array('%name' => $form_state['values']['name'])));
   'devel/variable';
}

/**
 * Form that displays all the config variables to edit them.
 *
 * @see devel_config_system_form_submit()
 */
function devel_config_system_form($form, &$form_state, $filter = '') {
  $form['filter'] = array(
    '#type' => 'details',
    '#title' => t('Filter variables'),
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filter']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Variable name'),
    '#title_display' => 'invisible',
    '#default_value' => $filter,
  );
  $form['filter']['show'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
  );
  $header = array(
    'name' => array('data' => t('Name')),
    'edit' => array('data' => t('Operations')),
  );
  $form['variables'] = array(
    '#type' => 'table',
    '#header' => $header,
  );

  // List all the variables filtered if any filter was provided.
  $names = config_get_storage_names_with_prefix($filter);
  foreach ($names as $key => $config_name) {
    $form['variables'][$key]['name'] = array('#markup' => $config_name);
    $form['variables'][$key]['operation'] = array('#markup' => l(t('Edit'), "devel/config/edit/$config_name"));
  }

  return $form;
}

/**
 * Submit handler for filter config variables list
 *
 * @see devel_config_system_form()
 */
function devel_config_system_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'devel/config/' . check_plain($form_state['values']['name']);
}

/**
 * Edit config variable form
 *
 * @see devel_config_system_edit_form()
 */
function devel_config_system_edit_form($form, &$form_state, $config_name) {
  $data = config($config_name)->get();
  if ($data === FALSE) {
    drupal_set_message(t('Config !name does not exist in the system.', array('!name' => $config_name)), 'error');
    return;
  }
  if (empty($data)) {
    drupal_set_message(t('Config !name exists but has no data.', array('!name' => $config_name)), 'warning');
    return;
  }
  $dumper = new Dumper();
  // Set Yaml\Dumper's default indentation for nested nodes/collections to
  // 2 spaces for consistency with Drupal coding standards.
  $dumper->setIndentation(2);
  // The level where you switch to inline YAML is set to PHP_INT_MAX to
  // ensure this does not occur.
  $output = $dumper->dump($data, PHP_INT_MAX);

  $form['name'] = array(
    '#type' => 'value',
    '#value' => $config_name,
  );

  $form['value'] = array(
    '#type' => 'item',
    '#title' => t('Old value for %variable', array('%variable' => $config_name)),
    '#markup' => dpr($output, TRUE),
  );

  $form['new'] = array(
    '#type' => 'textarea',
    '#title' => t('New value'),
    '#default_value' => $output,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  $form['actions']['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'devel/config',
  );

  return $form;
}

/**
 * Submit handler for edit config variable form
 *
 * @see devel_config_system_edit()
 */
function devel_config_system_edit_form_submit($form, &$form_state) {
  $parser = new Parser();
  $new_config = $parser->parse($form_state['values']['new']);
  config($form_state['values']['name'])->setData($new_config)->save();

  drupal_set_message(t('Configuration variable %variable was successfully saved.', array('%variable' => $form_state['values']['name'])));

  $form_state['redirect'] = 'devel/config';
}

/**
 * Page callback that lists all the state variables.
 */
function devel_state_system_page() {
  $page['states'] = array(
    '#type' => 'table',
    '#header' => array(
      'name' => array('data' => t('Name'), 'field' => 'name', 'sort' => 'asc'),
      'value' => array('data' => t('Value'), 'field' => 'value'),
      'edit' => array('data' => t('Operations')),
    ),
    '#empty' => t('No state variables.'),
  );

  // Get all states from the KeyValueStorage and put them in the table.
  foreach (state()->getAll() as $state_name => $state) {
    $page['states'][$state_name] = array(
      'name' => array('#markup' => $state_name),
      // Output value in krumo if necessary with kprint_r.
      'value' => array('#markup' => kprint_r($state, TRUE)),
      'edit' => array(
        '#type' => 'link',
        '#title' => t('Edit'),
        '#href' => 'devel/state/edit/' . $state_name,
      ),
    );
  }

  return $page;
}

/**
 * Form API form to edit a state.
 *
 * The user can only edit non-array and non-objec variables.
 */
function devel_state_system_edit_form($form, &$form_state, $key) {
  // Get the old value
  $old_value = state()->get($key);
  // First we will show the user the content of the variable about to be edited
  $form['old_value'] = array(
    '#type' => 'item',
    '#title' => t('Old value for %name', array('%name' => $key)),
    '#markup' => kprint_r($old_value, TRUE),
  );
  // Store in the form the name of the state variable
  $form['state_name'] = array(
    '#type' => 'hidden',
    '#value' => $key,
  );

  // Only simple structures are allowed to be editted.
  $disabled = !_devel_check_object($old_value);
  // Set the transport format for the new value. Values:
  //  - plain
  //  - yaml
  $form['transport'] = array(
    '#type' => 'hidden',
    '#value' => 'plain',
  );
  if (is_array($old_value)) {
    $dumper = new Dumper();
    // Set Yaml\Dumper's default indentation for nested nodes/collections to
    // 2 spaces for consistency with Drupal coding standards.
    $dumper->setIndentation(2);
    // The level where you switch to inline YAML is set to PHP_INT_MAX to
    // ensure this does not occur.
    $old_value = $dumper->dump($old_value, PHP_INT_MAX);
    $form['transport']['#value'] = 'yaml';
  }
  $form['new_value'] = array(
    '#type' => 'textarea',
    '#title' => t('New value'),
    '#default_value' => $disabled ? '' : $old_value,
    '#disabled' => $disabled,
  );

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  $form['actions']['cancel'] = array(
    '#type' => 'link',
    '#title' => t('Cancel'),
    '#href' => 'devel/state',
  );

  return $form;
}

/**
 * Helper function to determine if a variable is or contains an object.
 *
 * @param $data
 *   Input data to check
 * @return
 *   TRUE if the variable is not an object and does not contain one.
 */
function _devel_check_object($data) {
  if (is_object($data)) {
    return FALSE;
  }
  if (is_array($data)) {
    // If the current object is an array, then check recursively.
    foreach ($data as $key => $value) {
      // If there is an object the whole container is "contaminated"
      if (!_devel_check_object($value)) {
        return FALSE;
      }
    }
  }
  // All checks pass
  return TRUE;
}

/**
 * Submit callback for devel_state_system_edit_form
 */
function devel_state_system_edit_form_submit($form, &$form_state) {
  // Save the state
  $name = $form_state['values']['state_name'];
  switch ($form_state['values']['transport']) {
    case 'yaml':
      $parser = new Parser();
      $new_value = $parser->parse($form_state['values']['new_value']);
      break;

    default:
      $new_value = $form_state['values']['new_value'];
      break;
  }
  state()->set($name, $new_value);
  $form_state['redirect'] = 'devel/state';
  drupal_set_message(t('Variable %var was successfully edited.', array('%var' => $name)));
}

/**
 * Menu callback: display the session.
 */
function devel_session() {
  global $user;
  $output = kprint_r($_SESSION, TRUE);
  $headers = array(t('Session name'), t('Session ID'));
  $output .= theme('table', array('headers' => $headers, 'rows' => array(array(session_name(), session_id()))));
  return $output;
}

/**
 * Menu callback; prints the loaded structure of the current node/user.
 */
function devel_load_object($type, $object, $name = NULL) {
  $name = isset($name) ? $name : $type;
  return kdevel_print_object($object, '$'. $name .'->');
}

/**
 * Menu callback; prints the render structure of the current object (currently node or user).
 */
function devel_render_object($type, $object, $name = NULL) {
  $name = isset($name) ? $name : $type;
  $function = $type . '_view';
  $build = $function($object);
  return kdevel_print_object($build, '$'. $name .'->');
}

function devel_elements_page() {
  return kdevel_print_object(module_invoke_all('element_info'));
}

function devel_phpinfo() {
  print phpinfo();
  drupal_exit();
}
