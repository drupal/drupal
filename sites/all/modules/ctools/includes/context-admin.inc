<?php
// $Id: context-admin.inc,v 1.14 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file includes/common-context.inc
 * Provide API for adding contexts for modules that embed displays.
 *
 * Note that most of this code was directly copied from Panels 2, and as such
 * a lot of this code is crusty. It could probably stand to be rewritten,
 * and brought up to date, or at least better commented.
 */

/**
 * Provide a list of the ways contexts can be embedded.
 *
 * This provides a full list of context types that the tool understands
 * and can let modules utilize.
 */
function ctools_context_info($type = NULL) {
  static $info = NULL;

  // static doesn't work with functions like t().
  if (empty($info)) {
    $info = array(
      'argument' => array(
        'title' => t('Arguments'),
        'singular title' => t('argument'),
        'description' => '', // t("Arguments are parsed from the URL and translated into contexts that may be added to the display via the 'content' tab. These arguments are parsed in the order received, and you may use % in your URL to hold the place of an object; the rest of the arguments will come after the URL. For example, if the URL is node/%/panel and your user visits node/1/panel/foo, the first argument will be 1, and the second argument will be foo."),
        'add button' => t('Add argument'),
        'context function' => 'ctools_get_argument',
        'key' => 'arguments', // the key that data will be stored on an object, eg $panel_page
        'sortable' => TRUE,
        'settings' => 'argument_settings',
      ),
      'relationship' => array(
        'title' => t('Relationships'),
        'singular title' => t('relationship'),
        'description' => '', // t('Relationships are contexts that are created from already existing contexts; the add relationship button will only appear once there is another context available. Relationships can load objects based upon how they are related to each other; for example, the author of a node, or a taxonomy term attached to a node, or the vocabulary of a taxonomy term.'),
        'add button' => t('Add relationship'),
        'context function' => 'ctools_get_relationship',
        'key' => 'relationships',
        'sortable' => FALSE,
        'settings' => 'relationship_settings',
      ),
      'context' => array(
        'title' => t('Contexts'),
        'singular title' => t('context'),
        'description' => '', // t('Contexts are embedded directly into the panel; you generally must select an object in the panel. For example, you could select node 5, or the term "animals" or the user "administrator"'),
        'add button' => t('Add context'),
        'context function' => 'ctools_get_context',
        'key' => 'contexts',
        'sortable' => FALSE,
        'settings' => 'context_settings',
      ),
      'requiredcontext' => array(
        'title' => t('Required contexts'),
        'singular title' => t('required context'),
        'description' => '', // t('Required contexts are passed in from some external source, such as a containing panel. If a mini panel has required contexts, it can only appear when that context is available, and therefore will not show up as a standard Drupal block.'),
        'add button' => t('Add required context'),
        'context function' => 'ctools_get_context',
        'key' => 'requiredcontexts',
        'sortable' => FALSE,
      ),
    );
  }

  if ($type === NULL) {
    return $info;
  }

  return $info[$type];
}


/**
 * Get the data belonging to a particular context.
 */
function ctools_context_get_plugin($type, $name) {
  $info = ctools_context_info($type);
  if (function_exists($info['context function'])) {
    return $info['context function']($name);
  }
}

/**
 * Add the argument table plus gadget plus javascript to the form.
 */
function ctools_context_add_argument_form($module, &$form, &$form_state, &$form_location, $object, $cache_key = NULL) {
  if (empty($cache_key)) {
    $cache_key = $object->name;
  }

  $form_location = array(
    '#prefix' => '<div id="ctools-arguments-table">',
    '#suffix' => '</div>',
    '#theme' => 'ctools_context_item_form',
    '#cache_key' => $cache_key,
    '#ctools_context_type' => 'argument',
    '#ctools_context_module' => $module,
  );

  $args = ctools_get_arguments();
  $choices = array();
  foreach ($args as $name => $arg) {
    $choices[$name] = $arg['title'];
  }

  asort($choices);

  if (!empty($choices) || !empty($object->arguments)) {
    ctools_context_add_item_table('argument', $form_location, $choices, $object->arguments);
  }
}

function ctools_context_add_context_form($module, &$form, &$form_state, &$form_location, $object, $cache_key = NULL) {
  if (empty($cache_key)) {
    $cache_key = $object->name;
  }

  $form_location = array(
    '#prefix' => '<div id="ctools-contexts-table">',
    '#suffix' => '</div>',
    '#theme' => 'ctools_context_item_form',
    '#cache_key' => $cache_key,
    '#ctools_context_type' => 'context',
    '#ctools_context_module' => $module,
  );

  // Store the order the choices are in so javascript can manipulate it.
  $form_location['markup'] = array(
    '#markup' => '&nbsp;',
  );

  $choices = array();
  foreach (ctools_get_contexts() as $name => $arg) {
    if (empty($arg['no ui'])) {
      $choices[$name] = $arg['title'];
    }
  }

  asort($choices);

  if (!empty($choices) || !empty($object->contexts)) {
    ctools_context_add_item_table('context', $form_location, $choices, $object->contexts);
  }

}

function ctools_context_add_required_context_form($module, &$form, &$form_state, &$form_location, $object, $cache_key = NULL) {
  if (empty($cache_key)) {
    $cache_key = $object->name;
  }

  $form_location = array(
    '#prefix' => '<div id="ctools-requiredcontexts-table">',
    '#suffix' => '</div>',
    '#theme' => 'ctools_context_item_form',
    '#cache_key' => $cache_key,
    '#ctools_context_type' => 'requiredcontext',
    '#ctools_context_module' => $module,
  );

  // Store the order the choices are in so javascript can manipulate it.
  $form_location['markup'] = array(
    '#value' => '&nbsp;',
  );

  $choices = array();
  foreach (ctools_get_contexts() as $name => $arg) {
    $choices[$name] = $arg['title'];
  }

  asort($choices);

  if (!empty($choices) || !empty($object->contexts)) {
    ctools_context_add_item_table('requiredcontext', $form_location, $choices, $object->requiredcontexts);
  }
}

function ctools_context_add_relationship_form($module, &$form, &$form_state, &$form_location, $object, $cache_key = NULL) {
  if (empty($cache_key)) {
    $cache_key = $object->name;
  }

  $form_location = array(
    '#prefix' => '<div id="ctools-relationships-table">',
    '#suffix' => '</div>',
    '#theme' => 'ctools_context_item_form',
    '#cache_key' => $cache_key,
    '#ctools_context_type' => 'relationship',
    '#ctools_context_module' => $module,
  );

  // Store the order the choices are in so javascript can manipulate it.
  $form_location['markup'] = array(
    '#value' => '&nbsp;',
  );

  $base_contexts = isset($object->base_contexts) ? $object->base_contexts : array();
  $available_relationships = ctools_context_get_relevant_relationships(ctools_context_load_contexts($object, TRUE, $base_contexts));

  ctools_context_add_item_table('relationship', $form_location, $available_relationships, $object->relationships);
}

/**
 * Include all context administrative include files, css, javascript.
 */
function ctools_context_admin_includes() {
  ctools_include('context');
  ctools_include('modal');
  ctools_include('ajax');
  ctools_include('object-cache');
  ctools_modal_add_js();
  ctools_modal_add_plugin_js(ctools_get_contexts());
  ctools_modal_add_plugin_js(ctools_get_relationships());
}

/**
 * Add the context table to the page.
 */
function ctools_context_add_item_table($type, &$form, $available_contexts, $items) {
  $form[$type] = array(
    '#tree' => TRUE,
  );

  $module = $form['#ctools_context_module'];
  $cache_key   = $form['#cache_key'];

  if (isset($items) && is_array($items)) {
    foreach ($items as $position => $context) {
      ctools_context_add_item_to_form($module, $type, $cache_key, $form[$type][$position], $position, $context);
    }
  }

  $type_info = ctools_context_info($type);
  $form['description'] = array(
    '#prefix' => '<div class="description">',
    '#suffix' => '</div>',
    '#markup' => $type_info['description'],
  );

  ctools_context_add_item_table_buttons($type, $module, $form, $available_contexts);
}

function ctools_context_add_item_table_buttons($type, $module, &$form, $available_contexts) {
  drupal_add_js('misc/ajax.js');
  $form['buttons'] = array(
    '#tree' => TRUE,
  );

  if (!empty($available_contexts)) {
    $type_info = ctools_context_info($type);

    $module = $form['#ctools_context_module'];
    $cache_key   = $form['#cache_key'];

    // The URL for this ajax button
    $form['buttons'][$type]['add-url'] = array(
      '#attributes' => array('class' => array("ctools-$type-add-url")),
      '#type' => 'hidden',
      '#value' => url("ctools/context/ajax/add/$module/$type/$cache_key", array('absolute' => TRUE)),
    );

    // This also will be in the URL.
    $form['buttons'][$type]['item'] = array(
      '#attributes' => array('class' => array("ctools-$type-add-url")),
      '#type' => 'select',
      '#options' => $available_contexts,
      '#required' => FALSE,
    );

    $form['buttons'][$type]['add'] = array(
      '#type' => 'submit',
      '#attributes' => array('class' => array('ctools-use-modal')),
      '#id' => "ctools-$type-add",
      '#value' => $type_info['add button'],
    );
  }
}

/**
 * Add a row to the form. Used both in the main form and by
 * the ajax to add an item.
 */
function ctools_context_add_item_to_form($module, $type, $cache_key, &$form, $position, $item) {
  // This is the single function way to load any plugin by variable type.
  $info = ctools_context_get_plugin($type, $item['name']);
  $form['title'] = array(
    '#markup' => check_plain($item['identifier']),
  );

  // Relationships not sortable.
  $type_info = ctools_context_info($type);

  if (!empty($type_info['sortable'])) {
    $form['position'] = array(
      '#type' => 'weight',
      '#default_value' => $position,
      '#attributes' => array('class' => array('drag-position')),
    );
  }

  $form['remove'] = array(
    '#markup' => ctools_ajax_image_button(ctools_image_path('icon-delete.png'), "ctools/context/ajax/delete/$module/$type/$cache_key/$position", t('Remove this item.')),
  );

  $form['settings'] = array(
    '#markup' => ctools_modal_image_button(ctools_image_path('icon-configure.png'), "ctools/context/ajax/configure/$module/$type/$cache_key/$position", t('Configure settings for this item.')),
  );
}


// ---------------------------------------------------------------------------
// AJAX forms and stuff.

/**
 * Ajax entry point to add an context
 */
function ctools_context_ajax_item_add($mechanism = NULL, $type = NULL, $cache_key = NULL, $name = NULL, $step = NULL) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_include('context');
  ctools_include('cache');
  ctools_include('plugins-admin');

  if (!$name) {
    return ctools_ajax_render_error();
  }

  // Load stored object from cache.
  if (!($object = ctools_cache_get($mechanism, $cache_key))) {
    ctools_ajax_render_error(t('Invalid object name.'));
  }

  // Get info about what we're adding, i.e, relationship, context, argument, etc.
  $plugin_definition = ctools_context_get_plugin($type, $name);
  if (empty($plugin_definition)) {
    ctools_ajax_render_error(t('Invalid context type'));
  }

  // Set up the $conf array for this plugin
  if (empty($step) || empty($object->temporary)) {
    // Create the basis for our new context.
    $conf = ctools_context_get_defaults($plugin_definition);
    $object->temporary = &$conf;
  }
  else {
    $conf = &$object->temporary;
  }

  // Load the contexts that may be used.
  $base_contexts = isset($object->base_contexts) ? $object->base_contexts : array();
  $contexts = ctools_context_load_contexts($object, TRUE, $base_contexts);

  $type_info = ctools_context_info($type);
  $form_state = array(
    'ajax' => TRUE,
    'modal' => TRUE,
    'modal return' => TRUE,
    'object' => &$object,
    'conf' => &$conf,
    'plugin' => $plugin_definition,
    'type' => $type,
    'contexts' => $contexts,
    'title' => t('Add @type "@context"', array('@type' => $type_info['singular title'], '@context' => $plugin_definition['title'])),
    'type info' => $type_info,
    'op' => 'add',
    'step' => $step,
  );

  $form_info = array(
    'path' => "ctools/context/ajax/add/$mechanism/$type/$cache_key/$name/%step",
    'show cancel' => TRUE,
    'default form' => 'ctools_edit_context_form_defaults',
    'auto caching' => TRUE,
    'cache mechanism' => $mechanism,
    'cache key' => $cache_key,
    // This is stating what the cache will be referred to in $form_state
    'cache storage' => 'object',
  );

  $output = ctools_plugin_configure_form($form_info, $form_state);

  // If $rc is FALSE, there was no actual form.
  if ($output === FALSE || !empty($form_state['complete'])) {
    // Successful submit -- move temporary data to location.

    // Create a reference to the place our context lives. Since this is fairly
    // generic, this is the easiest way to get right to the place of the
    // object without knowing precisely what data we're poking at.
    $ref = &$object->{$type_info['key']};

    // Figure out the position for our new context.
    $position = empty($ref) ? 0 : max(array_keys($ref)) + 1;

    $conf['id'] = ctools_context_next_id($ref, $name);
    $ref[$position] = $conf;

    if (isset($object->temporary)) {
      unset($object->temporary);
    }

    ctools_cache_operation($mechanism, $cache_key, 'finalize', $object);

    // Very irritating way to update the form for our contexts.
    $arg_form_state = array('values' => array());

    $arg_form = array(
      '#post' => array(),
      '#programmed' => FALSE,
      '#tree' => FALSE,
    );

    // Build a chunk of the form to merge into the displayed form
    $arg_form[$type] = array(
      '#tree' => TRUE,
    );
    $arg_form[$type][$position] = array(
      '#tree' => TRUE,
    );

    ctools_context_add_item_to_form($mechanism, $type, $cache_key, $arg_form[$type][$position], $position, $ref[$position]);
    $arg_form = form_builder('ctools_context_form', $arg_form, $arg_form_state);

    // Build the relationships table so we can ajax it in.
    // This is an additional thing that goes in here.
    $rel_form = array(
      '#theme' => 'ctools_context_item_form',
      '#cache_key' => $cache_key,
      '#ctools_context_type' => 'relationship',
      '#ctools_context_module' => $mechanism,
      '#only_buttons' => TRUE,
      '#post' => array(),
      '#programmed' => FALSE,
      '#tree' => FALSE,
    );

    $rel_form['relationship'] = array(
      '#tree' => TRUE,
    );

    // Allow an object to set some 'base' contexts that come from elsewhere.
    $rel_contexts = isset($object->base_contexts) ? $object->base_contexts : array();
    $all_contexts = ctools_context_load_contexts($object, TRUE, $rel_contexts);
    $available_relationships = ctools_context_get_relevant_relationships($all_contexts);

    $output = array();
    if (!empty($available_relationships)) {
      ctools_context_add_item_table_buttons('relationship', $mechanism, $rel_form, $available_relationships);
      $rel_form = form_builder('dummy_form_id', $rel_form, $arg_form_state);
      $output[] = ajax_command_replace('div#ctools-relationships-table div.buttons', drupal_render($rel_form));
    }

    $theme_vars = array();
    $theme_vars['type'] = $type;
    $theme_vars['form'] = $arg_form[$type][$position];
    $theme_vars['position'] = $position;
    $theme_vars['count'] = $position;
    $text = theme('ctools_context_item_row', $theme_vars);
    $output[] = ajax_command_append('#' . $type . '-table tbody', $text);
    $output[] = ajax_command_changed('#' . $type . '-row-' . $position, '.title');
    $output[] = ctools_modal_command_dismiss();
  }
  else {
    $output = ctools_modal_form_render($form_state, $output);
  }
  print ajax_render($output);
  exit;
}

/**
 * Ajax entry point to edit an item
 */
function ctools_context_ajax_item_edit($mechanism = NULL, $type = NULL, $cache_key = NULL, $position = NULL, $step = NULL) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_include('context');
  ctools_include('cache');
  ctools_include('plugins-admin');

  if (!isset($position)) {
    return ctools_ajax_render_error();
  }

  // Load stored object from cache.
  if (!($object = ctools_cache_get($mechanism, $cache_key))) {
    ctools_ajax_render_error(t('Invalid object name.'));
  }

  $type_info = ctools_context_info($type);

  // Create a reference to the place our context lives. Since this is fairly
  // generic, this is the easiest way to get right to the place of the
  // object without knowing precisely what data we're poking at.
  $ref = &$object->{$type_info['key']};

  if (empty($step) || empty($object->temporary)) {
    // Create the basis for our new context.
    $conf = $object->{$type_info['key']}[$position];
    $object->temporary = &$conf;
  }
  else {
    $conf = &$object->temporary;
  }

  $name = $ref[$position]['name'];
  if (empty($name)) {
    ctools_ajax_render_error();
  }

  // load the plugin definition
  $plugin_definition = ctools_context_get_plugin($type, $name);
  if (empty($plugin_definition)) {
    ctools_ajax_render_error(t('Invalid context type'));
  }

  // Load the contexts
  $base_contexts = isset($object->base_contexts) ? $object->base_contexts : array();
  $contexts = ctools_context_load_contexts($object, TRUE, $base_contexts);

  $form_state = array(
    'ajax' => TRUE,
    'modal' => TRUE,
    'modal return' => TRUE,
    'object' => &$object,
    'conf' => &$conf,
    'position' => $position,
    'plugin' => $plugin_definition,
    'type' => $type,
    'contexts' => $contexts,
    'title' => t('Edit @type "@context"', array('@type' => $type_info['singular title'], '@context' => $plugin_definition['title'])),
    'type info' => $type_info,
    'op' => 'add',
    'step' => $step,
  );

  $form_info = array(
    'path' => "ctools/context/ajax/configure/$mechanism/$type/$cache_key/$position/%step",
    'show cancel' => TRUE,
    'default form' => 'ctools_edit_context_form_defaults',
    'auto caching' => TRUE,
    'cache mechanism' => $mechanism,
    'cache key' => $cache_key,
    // This is stating what the cache will be referred to in $form_state
    'cache storage' => 'object',
  );

  $output = ctools_plugin_configure_form($form_info, $form_state);

  // If $rc is FALSE, there was no actual form.
  if ($output === FALSE || !empty($form_state['complete'])) {
    // successful submit
    $ref[$position] = $conf;
    if (isset($object->temporary)) {
      unset($object->temporary);
    }

    ctools_cache_operation($mechanism, $cache_key, 'finalize', $object);

    $output = array();
    $output[] = ctools_modal_command_dismiss();

    $arg_form = array(
      '#post' => array(),
      '#programmed' => FALSE,
      '#tree' => FALSE,
    );

    // Build a chunk of the form to merge into the displayed form
    $arg_form[$type] = array(
      '#tree' => TRUE,
    );
    $arg_form[$type][$position] = array(
      '#tree' => TRUE,
    );

    ctools_context_add_item_to_form($mechanism, $type, $cache_key, $arg_form[$type][$position], $position, $ref[$position]);
    $arg_form = form_builder('ctools_context_form', $arg_form, $arg_form_state);

    $theme_vars = array();
    $theme_vars['type'] = $type;
    $theme_vars['form'] = $arg_form[$type][$position];
    $theme_vars['position'] = $position;
    $theme_vars['count'] = $position;
    $output[] = ajax_command_replace('#' . $type . '-row-' . $position, theme('ctools_context_item_row', $theme_vars));
    $output[] = ajax_command_changed('#' . $type . '-row-' . $position, '.title');
  }
  else {
    $output = ctools_modal_form_render($form_state, $output);
  }
  print ajax_render($output);
  exit;
}

function ctools_context_get_defaults($plugin_definition) {
  $conf = array(
    'identifier' => $plugin_definition['title'] . ($id > 1 ? ' ' . $id : ''),
    'keyword' => ctools_get_keyword($object, $plugin_definition['keyword']),
    'name' => $plugin_definition['name'],
  );

  if (isset($plugin['defaults'])) {
    $defaults = $plugin['defaults'];
  }
  else if (isset($subtype['defaults'])) {
    $defaults = $subtype['defaults'];
  }

  if (isset($defaults)) {
    if (is_string($defaults) && function_exists($defaults)) {
      if ($settings = $defaults($plugin_definition)) {
        $conf += $settings;
      }
    }
    else if (is_array($defaults)) {
      $conf += $defaults;
    }
  }

  return $conf;
}

/**
 * Form wrapper for the edit context form.
 *
 * @todo: We should uncombine these.
 */
function ctools_edit_context_form_defaults($form, &$form_state) {
  // Basic values required to orient ourselves
  $object            = $form_state['object'];
  $plugin_definition = $form_state['plugin'];
  $type_info         = $form_state['type info'];
  $position          = $form_state['position'];
  $contexts          = $form_state['contexts'];
  $conf             = $form_state['conf'];

  if ($type_info['key'] == 'arguments' && !isset($conf['default'])) {
    $conf['default'] = 'ignore';
    $conf['title'] = '';
  }

  $form['description'] = array(
    '#prefix' => '<div class="description">',
    '#suffix' => '</div>',
    '#markup' => check_plain($plugin_definition['description']),
  );

  if ($type_info['key'] == 'relationships') {
    $form['context'] = ctools_context_selector($contexts, $plugin_definition['required context'], isset($conf['context']) ? $conf['context'] : '');
  }
  if ($type_info['key'] == 'arguments') {
    $form['default'] = array(
      '#type' => 'select',
      '#title' => t('Default'),
      '#options' => array(
        'ignore' => t('Ignore it; content that requires this context will not be available.'),
        '404' => t('Display page not found or display nothing at all.'),
      ),
      '#default_value' => $conf['default'],
      '#description' => t('If the argument is missing or is not valid, select how this should behave.'),
    );

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $conf['title'],
      '#description' => t('Enter a title to use when this argument is present. You may use %KEYWORD substitution, where the keyword is specified below.'),
    );
  }

  $form['identifier'] = array(
    '#type' => 'textfield',
    '#title' => t('Identifier'),
    '#description' => t('Enter a name to identify this !type on administrative screens.', array('!type' => t('context'))),
    '#default_value' => $conf['identifier'],
  );

  $form['keyword'] = array(
    '#type' => 'textfield',
    '#title' => t('Keyword'),
    '#description' => t('Enter a keyword to use for substitution in titles.'),
    '#default_value' => $conf['keyword'],
  );

  $form['#submit'][] = 'ctools_edit_context_form_defaults_submit';

  return $form;
}

/**
 * Submit handler to store context identifier and keyword info.
 */
function ctools_edit_context_form_defaults_submit(&$form, &$form_state) {
  if ($form_state['type info']['key'] == 'relationships') {
    $form_state['conf']['context'] = $form_state['values']['context'];
  }
  if ($form_state['type info']['key'] == 'arguments') {
    $form_state['conf']['default'] = $form_state['values']['default'];
    $form_state['conf']['title'] = $form_state['values']['title'];
  }

  $form_state['conf']['identifier'] = $form_state['values']['identifier'];
  $form_state['conf']['keyword'] = $form_state['values']['keyword'];
}

/**
 * Ajax entry point to edit an item
 */
function ctools_context_ajax_item_delete($mechanism = NULL, $type = NULL, $cache_key = NULL, $position = NULL) {
  ctools_include('ajax');
  ctools_include('context');
  ctools_include('cache');

  if (!isset($position)) {
    return ctools_ajax_render_error();
  }

  // Load stored object from cache.
  if (!($object = ctools_cache_get($mechanism, $cache_key))) {
    ctools_ajax_render_error(t('Invalid object name.'));
  }

  $type_info = ctools_context_info($type);

  // Create a reference to the place our context lives. Since this is fairly
  // generic, this is the easiest way to get right to the place of the
  // object without knowing precisely what data we're poking at.
  $ref = &$object->{$type_info['key']};

  if (!array_key_exists($position, $ref)) {
    ctools_ajax_render_error(t('Unable to delete missing item!'));
  }

  unset($ref[$position]);
  ctools_cache_operation($mechanism, $cache_key, 'finalize', $object);

  $output   = array();
  $output[] = ajax_command_replace('#' . $type . '-row-' . $position, '');
  $output[] = ajax_command_restripe("#$type-table");
  print ajax_render($output);
  exit;
}

// --- End of contexts

function ctools_save_context($type, &$ref, $form_values) {
  $type_info = ctools_context_info($type);

  // Organize arguments
  $new = array();
  $order = array();

  foreach ($ref as $id => $context) {
    $position = $form_values[$type][$id]['position'];
    $order[$position] = $id;
  }

  ksort($order);
  foreach ($order as $id) {
    $new[] = $ref[$id];
  }
  $ref = $new;
}

function ctools_get_keyword($page, $word) {
  // Create a complete set of keywords
  $keywords = array();
  foreach (array('arguments', 'relationships', 'contexts', 'requiredcontexts') as $type) {
    if (!empty($page->$type) && is_array($page->$type)) {
      foreach ($page->$type as $info) {
        $keywords[$info['keyword']] = TRUE;
      }
    }
  }

  $keyword = $word;
  $count = 0;
  while (!empty($keywords[$keyword])) {
    $keyword = $word . '_' . ++$count;
  }
  return $keyword;
}

