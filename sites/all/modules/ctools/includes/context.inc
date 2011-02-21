<?php
// $Id: context.inc,v 1.44 2011/01/05 20:34:46 merlinofchaos Exp $

/**
 * @file
 *
 * Contains code related to the ctools system of 'context'.
 *
 * Context, originally from Panels, is a method of packaging objects into
 * a more generic bundle and providing a plugin system so that a UI can
 * take advantage of them. The idea is that the context objects
 * represent 'the context' that a given operation (usually a page view)
 * is operating in or on.
 *
 * For example, when viewing a page, the 'context' is a node object. When
 * viewing a user, the 'context' is a user object. Contexs can also
 * have related contexts. For example, when viewing a 'node' you may need
 * to know something about the node author. Therefore, the node author
 * is a related context.
 */

/**
 * The context object is largely a wrapper around some other object, with
 * an interface to finding out what is contained and getting to both
 * the object and information about the object.
 *
 * Each context object has its own information, but some things are very
 * common, such as titles, data, keywords, etc. In particulare, the 'type'
 * of the context is important.
 */
class ctools_context {
  var $type = NULL;
  var $data = NULL;
  // The title of this object.
  var $title = '';
  // The title of the page if this object exists
  var $page_title = '';
  // The identifier (in the UI) of this object
  var $identifier = '';
  var $argument = NULL;
  var $keyword = '';
  var $original_argument = NULL;
  var $restrictions = array();
  var $empty = FALSE;

  function ctools_context($type = 'none', $data = NULL) {
    $this->type  = $type;
    $this->data  = $data;
    $this->title = t('Unknown context');
  }

  function is_type($type) {
    if ($type == 'any' || $this->type == 'any') {
      return TRUE;
    }

    $a = is_array($type) ? $type : array($type);
    $b = is_array($this->type) ? $this->type : array($this->type);
    return (bool) array_intersect($a, $b);
  }

  function get_argument() {
    return $this->argument;
  }

  function get_original_argument() {
    if (!is_null($this->original_argument)) {
      return $this->original_argument;
    }
    return $this->argument;
  }

  function get_keyword() {
    return $this->keyword;
  }

  function get_identifier() {
    return $this->identifier;
  }

  function get_title() {
    return $this->title;
  }

  function get_page_title() {
    return $this->page_title;
  }
}

/**
 * Used to create a method of comparing if a list of contexts
 * match a required context type.
 */
class ctools_context_required {
  var $keywords = '';

  /**
   * If set, the title will be used in the selector to identify
   * the context. This is very useful when multiple contexts
   * are required to inform the user will be used for what.
   */
  var $title = NULL;

  /**
   * Test to see if this context is required.
   */
  var $required = TRUE;

  /**
   *
   * @param $title
   *   The first parameter should be the 'title' of the context for use
   *   in UYI selectors when multiple contexts qualify.
   * @param ...
   *   One or more keywords to use for matching which contexts are allowed.
   */
  function ctools_context_required($title) {
    $args = func_get_args();
    $this->title = array_shift($args);

    // If we were given restrictions at the end, store them.
    if (count($args) > 1 && is_array(end($args))) {
      $this->restrictions = array_pop($args);
    }

    if (count($args) == 1) {
      $args = array_shift($args);
    }
    $this->keywords = $args;
  }

  function filter($contexts) {
    $result = array();

    // See which of these contexts are valid
    foreach ((array) $contexts as $cid => $context) {
      if ($context->is_type($this->keywords)) {
        // Compare to see if our contexts were met.
        if (!empty($this->restrictions) && !empty($context->restrictions)) {
          foreach ($this->restrictions as $key => $values) {
            // If we have a restriction, the context must either not have that
            // restriction listed, which means we simply don't know what it is,
            // or there must be an intersection of the restricted values on
            // both sides.
            if (!is_array($values)) {
              $values = array($values);
            }
            if (!empty($context->restrictions[$key]) && !array_intersect($values, $context->restrictions[$key])) {
              continue 2;
            }
          }
        }
        $result[$cid] = $context;
      }
    }

    return $result;
  }

  function select($contexts, $context) {
    if (!is_array($contexts)) {
      $contexts = array($contexts);
    }

    // Due to a bug, some contexts got recorded with an id of 0. This will
    // convert them to the correct ID allowing for some earlier panels
    // to continue to work.
    if (!empty($context) && $context[strlen($context) - 1] === '0') {
      $context[strlen($context) - 1] = 1;
    }

    if (empty($context) || empty($contexts[$context])) {
      return FALSE;
    }
    return $contexts[$context];
  }
}

/**
 * Used to compare to see if a list of contexts match an optional context. This
 * can produce empty contexts to use as placeholders.
 */
class ctools_context_optional extends ctools_context_required {
  var $required = FALSE;
  function ctools_context_optional() {
    $args = func_get_args();
    call_user_func_array(array($this, 'ctools_context_required'), $args);
  }

  /**
   * Add the 'empty' context which is possible for optional
   */
  function add_empty(&$contexts) {
    $context = new ctools_context('any');
    $context->title      = t('No context');
    $context->identifier = t('No context');
    $contexts = array_merge(array('empty' => $context), $contexts);
  }

  function filter($contexts) {
    $this->add_empty($contexts);
    return parent::filter($contexts);
  }

  function select($contexts, $context) {
    $this->add_empty($contexts);
    if (empty($context)) {
      return $contexts['empty'];
    }

    $result = parent::select($contexts, $context);

    // Don't flip out if it can't find the context; this is optional, put
    // in an empty.
    if ($result == FALSE) {
      $result = $contexts['empty'];
    }
    return $result;
  }
}

/**
 * Return a keyed array of context that match the given 'required context'
 * filters.
 *
 * Functions or systems that require contexts of a particular type provide a
 * ctools_context_required or ctools_context_optional object. This function
 * examines that object and an array of contexts to determine which contexts
 * match the filter.
 *
 * Since multiple contexts can be required, this function will accept either
 * an array of all required contexts, or just a single required context object.
 *
 * @param $contexts
 *   A keyed array of all available contexts.
 * @param $required
 *   A ctools_context_required or ctools_context_optional object, or an array
 *   of such objects.
 *
 * @return
 *   A keyed array of contexts that match the filter.
 */
function ctools_context_filter($contexts, $required) {
  if (is_array($required)) {
    $result = array();
    foreach ($required as $r) {
      $result = array_merge($result, _ctools_context_filter($contexts, $r));
    }
    return $result;
  }

  return _ctools_context_filter($contexts, $required);
}

function _ctools_context_filter($contexts, $required) {
  $result = array();

  if (is_object($required)) {
    $result = $required->filter($contexts);
  }

  return $result;
}

/**
 * Create a select box to choose possible contexts.
 *
 * This only creates a selector if there is actually a choice; if there
 * is only one possible context, that one is silently assigned.
 *
 * If an array of required contexts is provided, one selector will be
 * provided for each context.
 *
 * @param $contexts
 *   A keyed array of all available contexts.
 * @param $required
 *   The required context object or array of objects.
 *
 * @return
 *   A form element, or NULL if there are no contexts that satisfy the
 *   requirements.
 */
function ctools_context_selector($contexts, $required, $default) {
  if (is_array($required)) {
    $result = array('#tree' => TRUE);
    $count = 1;
    foreach ($required as $id => $r) {
      $result[] = _ctools_context_selector($contexts, $r, isset($default[$id]) ? $default[$id] : '', $count++);
    }
    return $result;
  }

  return _ctools_context_selector($contexts, $required, $default);
}

function _ctools_context_selector($contexts, $required, $default, $num = 0) {
  $filtered = ctools_context_filter($contexts, $required);
  $count = count($filtered);

  $form = array();

  if ($count >= 1) {
    // If there's more than one to choose from, create a select widget.
    foreach ($filtered as $cid => $context) {
      $options[$cid] = $context->get_identifier();
    }
    if (!empty($required->title)) {
      $title = $required->title;
    }
    else {
      $title = $num ? t('Context %count', array('%count' => $num)) : t('Context');
    }

    return array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $title,
      '#default_value' => $default,
    );
  }
}

/**
 * Are there enough contexts for a plugin?
 *
 * Some plugins can have a 'required contexts' item which can either
 * be a context requirement object or an array of them. When contexts
 * are required, items that do not have enough contexts should not
 * appear. This tests an item to see if it has enough contexts
 * to actually appear.
 *
 * @param $contexts
 *   A keyed array of all available contexts.
 * @param $required
 *   The required context object or array of objects.
 *
 * @return
 *   TRUE if there are enough contexts, FALSE if there are not.
 */
function ctools_context_match_requirements($contexts, $required) {
  if (!is_array($required)) {
    $required = array($required);
  }

  // Get the keys to avoid bugs in PHP 5.0.8 with keys and loops.
  // And use it to remove optional contexts.
  $keys = array_keys($required);
  foreach ($keys as $key) {
    if (empty($required[$key]->required)) {
      unset($required[$key]);
    }
  }

  $count = count($required);
  return (count(ctools_context_filter($contexts, $required)) >= $count);
}

/**
 * Create a select box to choose possible contexts.
 *
 * This only creates a selector if there is actually a choice; if there
 * is only one possible context, that one is silently assigned.
 *
 * If an array of required contexts is provided, one selector will be
 * provided for each context.
 *
 * @param $contexts
 *   A keyed array of all available contexts.
 * @param $required
 *   The required context object or array of objects.
 *
 * @return
 *   A form element, or NULL if there are no contexts that satisfy the
 *   requirements.
 */
function ctools_context_converter_selector($contexts, $required, $default) {
  if (is_array($required)) {
    $result = array('#tree' => TRUE);
    $count = 1;
    foreach ($required as $id => $r) {
      $result[] = _ctools_context_converter_selector($contexts, $r, isset($default[$id]) ? $default[$id] : '', $count++);
    }
    return $result;
  }

  return _ctools_context_converter_selector($contexts, $required, $default);
}

function _ctools_context_converter_selector($contexts, $required, $default, $num = 0) {
  $filtered = ctools_context_filter($contexts, $required);
  $count = count($filtered);

  $form = array();

  if ($count > 1) {
    // If there's more than one to choose from, create a select widget.
    $options = array();
    foreach ($filtered as $cid => $context) {
      if ($context->type == 'any') {
        $options[''] = t('No context');
        continue;
      }
      $key = $context->get_identifier();
      if ($converters = ctools_context_get_converters($cid . '.', $context)) {
        $options[$key] = $converters;
      }
    }
    if (empty($options)) {
      return array(
        '#type' => 'value',
        '#value' => 'any',
      );
    }
    if (!empty($required->title)) {
      $title = $required->title;
    }
    else {
      $title = $num ? t('Context %count', array('%count' => $num)) : t('Context');
    }

    return array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $title,
      '#description' => t('Please choose which context and how you would like it converted.'),
      '#default_value' => $default,
    );
  }
}

/**
 * Get a list of converters available for a given context.
 */
function ctools_context_get_converters($cid, $context) {
  if (empty($context->plugin)) {
    return array();
  }

  return _ctools_context_get_converters($cid, $context->plugin);
}

/**
 * Get a list of converters available for a given context.
 */
function _ctools_context_get_converters($id, $plugin_name) {
  $plugin = ctools_get_context($plugin_name);
  if (empty($plugin['convert list'])) {
    return array();
  }

  $converters = array();
  if (is_array($plugin['convert list'])) {
    $converters = $plugin['convert list'];
  }
  else if ($function = ctools_plugin_get_function($plugin, 'convert list')) {
    $converters = (array) $function($plugin);
  }

  foreach (module_implements('ctools_context_convert_list_alter') as $module) {
    $function = $module . '_ctools_context_convert_list_alter';
    $function($plugin, $converters);
  }

  // Now, change them all to include the plugin:
  $return = array();
  foreach ($converters as $key => $title) {
    $return[$id . $key] = $title;
  }

  natcasesort($return);
  return $return;
}

/**
 * Get a list of all contexts + converters available.
 */
function ctools_context_get_all_converters() {
  $contexts = ctools_get_contexts();
  $converters = array();
  foreach ($contexts as $name => $context) {
    $context_converters = _ctools_context_get_converters($name . '.', $name);
    if ($context_converters) {
      $converters[$context['title']] = $context_converters;
    }
  }

  return $converters;
}

/**
 * Let the context convert an argument based upon the converter that was given.
 */
function ctools_context_convert_context($context, $converter) {
  // Contexts without plugins might be optional placeholders.
  if (empty($context->plugin)) {
    return;
  }

  $value = $context->argument;
  if ($function = ctools_plugin_load_function('ctools', 'contexts', $context->plugin, 'convert')) {
    $value = $function($context, $converter);
  }

  foreach (module_implements('ctools_context_converter_alter') as $module) {
    $function = $module . '_ctools_context_converter_alter';
    $function($context, $converter, $value);
  }

  return $value;
}

/**
 * Choose a context or contexts based upon the selection made via
 * ctools_context_filter.
 *
 * @param $contexts
 *   A keyed array of all available contexts
 * @param $required
 *   The required context object provided by the plugin
 * @param $context
 *   The selection made using ctools_context_selector
 */
function ctools_context_select($contexts, $required, $context) {
  if (is_array($required)) {
    $result = array();
    foreach ($required as $id => $r) {
      if (($result[] = _ctools_context_select($contexts, $r, $context[$id])) === FALSE) {
        return FALSE;
      }
    }
    return $result;
  }

  return _ctools_context_select($contexts, $required, $context);
}

function _ctools_context_select($contexts, $required, $context) {
  if (!is_object($required)) {
    return FALSE;
  }

  return $required->select($contexts, $context);
}

/**
 * Create a new context object.
 *
 * @param $type
 *   The type of context to create; this loads a plugin.
 * @param $data
 *   The data to put into the context.
 * @param $empty
 *   Whether or not this context is specifically empty.
 * @param $conf
 *   A configuration structure if this context was created via UI.
 *
 * @return
 *   A $context or NULL if one could not be created.
 */
function ctools_context_create($type, $data = NULL, $conf = FALSE) {
  ctools_include('plugins');
  $plugin = ctools_get_context($type);
  if ($function = ctools_plugin_load_function('ctools', 'contexts', $type, 'context')) {
    return $function(FALSE, $data, $conf, $plugin);
  }
}

/**
 * Create an empty context object.
 *
 * Empty context objects are primarily used as placeholders in the UI where
 * the actual contents of a context object may not be known. It may have
 * additional text embedded to give the user clues as to how the context
 * is used.
 *
 * @param $type
 *   The type of context to create; this loads a plugin.
 *
 * @return
 *   A $context or NULL if one could not be created.
 */
function ctools_context_create_empty($type) {
  $plugin = ctools_get_context($type);
  if ($function = ctools_plugin_get_function($plugin, 'context')) {
    $context = $function(TRUE, NULL, FALSE, $plugin);
    if (is_object($context)) {
      $context->empty = TRUE;
    }

    return $context;
  }
}

/**
 * Perform keyword and context substitutions.
 */
function ctools_context_keyword_substitute($string, $keywords, $contexts) {
  // Ensure a default keyword exists:
  $keywords['%%'] = '%';

  // Match contexts to the base keywords:
  $context_keywords = array();
  foreach ($contexts as $context) {
    if (isset($context->keyword)) {
      $context_keywords[$context->keyword] = $context;
    }
  }

  // Look for context matches we we only have to convert known matches.
  $matches = array();
  if (preg_match_all('/%([a-zA-Z0-9%:_-]+)/us', $string, $matches)) {
    foreach ($matches[1] as $keyword) {
      // Ignore anything it finds with %%.
      if ($keyword[0] == '%') {
        continue;
      }

      // If the keyword is already set by something passed in, don't try to
      // overwrite it.
      if (!empty($keywords['%' . $keyword])) {
        continue;
      }

      // Figure out our keyword and converter, if specified.
      if (strpos($keyword, ':')) {
        list($context, $converter) = explode(':', $keyword, 2);
      }
      else {
        $context = $keyword;
        if (isset($context_keywords[$keyword])) {
          $plugin = ctools_get_context($context_keywords[$context]->plugin);

          // Fall back to a default converter, if specified.
          if ($plugin && !empty($plugin['convert default'])) {
            $converter = $plugin['convert default'];
          }
        }
      }

      if (empty($context_keywords[$context]) || !empty($context_keywords[$context]->empty)) {
        $keywords['%' . $keyword] = '';
      }
      else if (!empty($converter)) {
        $keywords['%' . $keyword] = ctools_context_convert_context($context_keywords[$context], $converter);
      }
      else {
        $keywords['%' . $keyword] = $context_keywords[$keyword]->title;
      }
    }
  }
  return strtr($string, $keywords);
}

/**
 * Determine a unique context ID for a context
 *
 * Often contexts of many different types will be placed into a list. This
 * ensures that even though contexts of multiple types may share IDs, they
 * are unique in the final list.
 */
function ctools_context_id($context, $type = 'context') {
  if (!$context['id']) {
    $context['id'] = 1;
  }

  return $type . '_' . $context['name'] . '_' . $context['id'];
}

/**
 * Get the next id available given a list of already existing objects.
 *
 * This finds the next id available for the named object.
 *
 * @param $objects
 *   A list of context descriptor objects, i.e, arguments, relationships, contexts, etc.
 * @param $name
 *   The name being used.
 */
function ctools_context_next_id($objects, $name) {
  $id = 0;
  // Figure out which instance of this argument we're creating
  if (!$objects) {
    return $id + 1;
  }

  foreach ($objects as $object) {
    if (isset($object['name']) && $object['name'] == $name) {
      if ($object['id'] > $id) {
        $id = $object['id'];
      }
    }
  }

  return $id + 1;
}


// ---------------------------------------------------------------------------
// Functions related to contexts from arguments.

/**
 * Fetch metadata on a specific argument plugin.
 *
 * @param $argument
 *   Name of an argument plugin.
 *
 * @return
 *   An array with information about the requested argument plugin.
 */
function ctools_get_argument($argument) {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'arguments', $argument);
}

/**
 * Fetch metadata for all argument plugins.
 *
 * @return
 *   An array of arrays with information about all available argument plugins.
 */
function ctools_get_arguments() {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'arguments');
}

/**
 * Get a context from an argument.
 *
 * @param $argument
 *   The configuration of an argument. It must contain the following data:
 *   - name: The name of the argument plugin being used.
 *   - argument_settings: The configuration based upon the plugin forms.
 *   - identifier: The human readable identifier for this argument, usually
 *     defined by the UI.
 *   - keyword: The keyword used for this argument for substitutions.
 *
 * @param $arg
 *   The actual argument received. This is expected to be a string from a URL but
 *   this does not have to be the only source of arguments.
 * @param $empty
 *   If true, the $arg will not be used to load the context. Instead, an empty
 *   placeholder context will be loaded.
 *
 * @return
 *   A context object if one can be loaded.
 */
function ctools_context_get_context_from_argument($argument, $arg, $empty = FALSE) {
  ctools_include('plugins');
  if (empty($argument['name'])) {
    return;
  }

  if ($function = ctools_plugin_load_function('ctools', 'arguments', $argument['name'], 'context')) {
    // Backward compatibility: Merge old style settings into new style:
    if (!empty($argument['settings'])) {
      $argument += $argument['settings'];
      unset($argument['settings']);
    }

    $context = $function($arg, $argument, $empty);

    if (is_object($context)) {
      $context->identifier = $argument['identifier'];
      $context->page_title = isset($argument['title']) ? $argument['title'] : '';
      $context->keyword    = $argument['keyword'];
      $context->id         = ctools_context_id($argument, 'argument');
      $context->original_argument = $arg;

      if (!empty($context->empty)) {
        $context->placeholder = array(
          'type' => 'argument',
          'conf' => $argument,
        );
      }
    }
    return $context;
  }
}

/**
 * Retrieve a list of empty contexts for all arguments.
 */
function ctools_context_get_placeholders_from_argument($arguments) {
  $contexts = array();
  foreach ($arguments as $argument) {
    $context = ctools_context_get_context_from_argument($argument, NULL, TRUE);
    if ($context) {
      $contexts[ctools_context_id($argument, 'argument')] = $context;
    }
  }
  return $contexts;
}

/**
 * Load the contexts for a given list of arguments.
 *
 * @param $arguments
 *   The array of argument definitions.
 * @param &$contexts
 *   The array of existing contexts. New contexts will be added to this array.
 * @param $args
 *   The arguments to load.
 *
 * @return
 *   FALSE if an argument wants to 404.
 */
function ctools_context_get_context_from_arguments($arguments, &$contexts, $args) {
  foreach ($arguments as $argument) {
    // pull the argument off the list.
    $arg = array_shift($args);
    $id = ctools_context_id($argument, 'argument');

    // For % arguments embedded in the URL, our context is already loaded.
    // There is no need to go and load it again.
    if (empty($contexts[$id])) {
      if ($context = ctools_context_get_context_from_argument($argument, $arg)) {
        $contexts[$id] = $context;
      }
    }
    else {
      $context = $contexts[$id];
    }

    if ((empty($context) || empty($context->data)) && !empty($argument['default']) && $argument['default'] == '404') {
      return FALSE;
    }
  }
  return TRUE;
}

// ---------------------------------------------------------------------------
// Functions related to contexts from relationships.

/**
 * Fetch metadata on a specific relationship plugin.
 *
 * @param $content type
 *   Name of a panel content type.
 *
 * @return
 *   An array with information about the requested relationship.
 */
function ctools_get_relationship($relationship) {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'relationships', $relationship);
}

/**
 * Fetch metadata for all relationship plugins.
 *
 * @return
 *   An array of arrays with information about all available relationships.
 */
function ctools_get_relationships() {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'relationships');
}

/**
 *
 * @param $relationship
 *   The configuration of a relationship. It must contain the following data:
 *   - name: The name of the relationship plugin being used.
 *   - relationship_settings: The configuration based upon the plugin forms.
 *   - identifier: The human readable identifier for this relationship, usually
 *     defined by the UI.
 *   - keyword: The keyword used for this relationship for substitutions.
 *
 * @param $source_context
 *   The context this relationship is based upon.
 *
 * @param $placeholders
 *   If TRUE, placeholders are acceptable.
 *
 * @return
 *   A context object if one can be loaded.
 */
function ctools_context_get_context_from_relationship($relationship, $source_context, $placeholders = FALSE) {
  ctools_include('plugins');
  if ($function = ctools_plugin_load_function('ctools', 'relationships', $relationship['name'], 'context')) {
    // Backward compatibility: Merge old style settings into new style:
    if (!empty($relationship['relationship_settings'])) {
      $relationship += $relationship['relationship_settings'];
      unset($relationship['relationship_settings']);
    }

    $context = $function($source_context, $relationship, $placeholders);
    if ($context) {
      $context->identifier = $relationship['identifier'];
      $context->page_title = isset($relationship['title']) ? $relationship['title'] : '';
      $context->keyword    = $relationship['keyword'];
      if (!empty($context->empty)) {
        $context->placeholder = array(
          'type' => 'relationship',
          'conf' => $relationship,
        );
      }
      return $context;
    }
  }
}

/**
 * Fetch all relevant relationships.
 *
 * Relevant relationships are any relationship that can be created based upon
 * the list of existing contexts. For example, the 'node author' relationship
 * is relevant if there is a 'node' context, but makes no sense if there is
 * not one.
 *
 * @param $contexts
 *   An array of contexts used to figure out which relationships are relevant.
 *
 * @return
 *   An array of relationship keys that are relevant for the given set of
 *   contexts.
 */
function ctools_context_get_relevant_relationships($contexts) {
  $relevant = array();
  $relationships = ctools_get_relationships();

  // Go through each relationship
  foreach ($relationships as $rid => $relationship) {
    // For each relationship, see if there is a context that satisfies it.
    if (ctools_context_filter($contexts, $relationship['required context'])) {
      $relevant[$rid] = $relationship['title'];
    }
  }

  return $relevant;
}

/**
 * Fetch all active relationships
 *
 * @param $relationships
 *   An keyed array of relationship data including:
 *   - name: name of relationship
 *   - context: context id relationship belongs to. This will be used to
 *     identify which context in the $contexts array to use to create the
 *     relationship context.
 *
 * @param $contexts
 *   A keyed array of contexts used to figure out which relationships
 *   are relevant. New contexts will be added to this.
 *
 * @param $placeholders
 *   If TRUE, placeholders are acceptable.
 */
function ctools_context_get_context_from_relationships($relationships, &$contexts, $placeholders = FALSE) {
  $return = array();

  foreach ($relationships as $rdata) {
    if (!isset($rdata['context'])) {
      continue;
    }

    if (is_array($rdata['context'])) {
      $rcontexts = array();
      foreach ($rdata['context'] as $cid) {
        if (empty($contexts[$cid])) {
          continue 2;
        }
        $rcontexts[] = $contexts[$cid];
      }
    }
    else {
      if (empty($contexts[$rdata['context']])) {
        continue;
      }
      $rcontexts = $contexts[$rdata['context']];
    }

    $cid = ctools_context_id($rdata, 'relationship');
    if ($context = ctools_context_get_context_from_relationship($rdata, $rcontexts)) {
      $contexts[$cid] = $context;
    }
  }
}

// ---------------------------------------------------------------------------
// Functions related to loading contexts from simple context definitions.

/**
 * Fetch metadata on a specific context plugin.
 *
 * @param $context
 *   Name of a context.
 *
 * @return
 *   An array with information about the requested panel context.
 */
function ctools_get_context($context) {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'contexts', $context);
}

/**
 * Fetch metadata for all context plugins.
 *
 * @return
 *   An array of arrays with information about all available panel contexts.
 */
function ctools_get_contexts() {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'contexts');
}

/**
 *
 * @param $context
 *   The configuration of a context. It must contain the following data:
 *   - name: The name of the context plugin being used.
 *   - context_settings: The configuration based upon the plugin forms.
 *   - identifier: The human readable identifier for this context, usually
 *     defined by the UI.
 *   - keyword: The keyword used for this context for substitutions.
 * @param $type
 *   This is either 'context' which indicates the context will be loaded
 *   from data in the settings, or 'required_context' which means the
 *   context must be acquired from an external source. This is the method
 *   used to pass pure contexts from one system to another.
 *
 * @return
 *   A context object if one can be loaded.
 */
function ctools_context_get_context_from_context($context, $type = 'context', $argument = NULL) {
  ctools_include('plugins');
  $plugin = ctools_get_context($context['name']);
  if ($function = ctools_plugin_get_function($plugin, 'context')) {
    // Backward compatibility: Merge old style settings into new style:
    if (!empty($context['context_settings'])) {
      $context += $context['context_settings'];
      unset($context['context_settings']);
    }

    if (isset($argument) && isset($plugin['placeholder name'])) {
      $context[$plugin['placeholder name']] = $argument;
    }

    $return = $function($type == 'requiredcontext', $context, TRUE, $plugin);
    if ($return) {
      $return->identifier = $context['identifier'];
      $return->page_title = isset($context['title']) ? $context['title'] : '';
      $return->keyword    = $context['keyword'];

      if (!empty($context->empty)) {
        $context->placeholder = array(
          'type' => 'context',
          'conf' => $context,
        );
      }

      return $return;
    }
  }
}

/**
 * Retrieve a list of base contexts based upon a simple 'contexts' definition.
 *
 * For required contexts this will always retrieve placeholders.
 *
 * @param $contexts
 *   The list of contexts defined in the UI.
 * @param $type
 *   Either 'context' or 'requiredcontext', which indicates whether the contexts
 *   are loaded from internal data or copied from an external source.
 * @param $placeholders
 *   If true, placeholders are acceptable.
 */
function ctools_context_get_context_from_contexts($contexts, $type = 'context', $placeholders = FALSE) {
  $return = array();
  foreach ($contexts as $context) {
    $ctext = ctools_context_get_context_from_context($context, $type);
    if ($ctext) {
      if ($placeholders) {
        $ctext->placeholder = TRUE;
      }
      $return[ctools_context_id($context, $type)] = $ctext;
    }
  }
  return $return;
}

/**
 * Match up external contexts to our required contexts.
 *
 * This function is used to create a list of contexts with proper
 * IDs based upon a list of required contexts.
 *
 * These contexts passed in should match the numeric positions of the
 * required contexts. The caller must ensure this has already happened
 * correctly as this function will not detect errors here.
 *
 * @param $required
 *   A list of required contexts as defined by the UI.
 * @param $contexts
 *   A list of matching contexts as passed in from the calling system.
 */
function ctools_context_match_required_contexts($required, $contexts) {
  $return = array();
  if (!is_array($required)) {
    return $return;
  }

  foreach ($required as $r) {
    $context = drupal_clone(array_shift($contexts));
    $context->identifier = $r['identifier'];
    $context->page_title = isset($r['title']) ? $r['title'] : '';
    $context->keyword    = $r['keyword'];
    $return[ctools_context_id($r, 'requiredcontext')] = $context;
  }

  return $return;
}

/**
 * Load a full array of contexts for an object.
 *
 * Not all of the types need to be supported by this object.
 *
 * This function is not used to load contexts from external data, but may
 * be used to load internal contexts and relationships. Otherwise it can also
 * be used to generate a full set of placeholders for UI purposes.
 *
 * @param $object
 *   An object that contains some or all of the following variables:
 *
 * - requiredcontexts: A list of UI configured contexts that are required
 *   from an external source. Since these require external data, they will
 *   only be added if $placeholders is set to TRUE, and empty contexts will
 *   be created.
 * - arguments: A list of UI configured arguments that will create contexts.
 *   Since these require external data, they will only be added if $placeholders
 *   is set to TRUE.
 * - contexts: A list of UI configured contexts that have no external source,
 *   and are essentially hardcoded. For example, these might configure a
 *   particular node or a particular taxonomy term.
 * - relationships: A list of UI configured contexts to be derived from other
 *   contexts that already exist from other sources. For example, these might
 *   be used to get a user object from a node via the node author relationship.
 * @param $placeholders
 *   If TRUE, this will generate placeholder objects for types this function
 *   cannot load.
 * @param $contexts
 *   An array of pre-existing contexts that will be part of the return value.
 */
function ctools_context_load_contexts($object, $placeholders = TRUE, $contexts = array()) {
  if (!empty($object->base_contexts)) {
    $contexts += $object->base_contexts;
  }

  if ($placeholders) {
    // This will load empty contexts as placeholders for arguments that come
    // from external sources. If this isn't set, it's assumed these context
    // will already have been matched up and loaded.
    if (!empty($object->requiredcontexts) && is_array($object->requiredcontexts)) {
      $contexts += ctools_context_get_context_from_contexts($object->requiredcontexts, 'requiredcontext', $placeholders);
    }

    if (!empty($object->arguments) && is_array($object->arguments)) {
      $contexts += ctools_context_get_placeholders_from_argument($object->arguments);
    }
  }

  if (!empty($object->contexts) && is_array($object->contexts)) {
    $contexts += ctools_context_get_context_from_contexts($object->contexts, 'context', $placeholders);
  }

  // add contexts from relationships
  if (!empty($object->relationships) && is_array($object->relationships)) {
    ctools_context_get_context_from_relationships($object->relationships, $contexts, $placeholders);
  }

  return $contexts;
}

/**
 * Return the first context with a form id from a list of contexts.
 *
 * This function is used to figure out which contexts represents 'the form'
 * from a list of contexts. Only one contexts can actually be 'the form' for
 * a given page, since the @code{<form>} tag can not be embedded within
 * itself.
 */
function ctools_context_get_form($contexts) {
  if (!empty($contexts)) {
    foreach ($contexts as $id => $context) {
      // if a form shows its id as being a 'required context' that means the
      // the context is external to this display and does not count.
      if (!empty($context->form_id) && substr($id, 0, 15) != 'requiredcontext') {
        return $context;
      }
    }
  }
}

/**
 * Replace placeholders with real contexts using data extracted from a form
 * for the purposes of previews.
 *
 * @param $contexts
 *   All of the contexts, including the placeholders.
 * @param $arguments
 *   The arguments. These will be acquired from $form_state['values'] and the
 *   keys must match the context IDs.
 *
 * @return
 *   A new $contexts array containing the replaced contexts. Not all contexts
 *   may be replaced if, for example, an argument was unable to be converted
 *   into a context.
 */
function ctools_context_replace_placeholders($contexts, $arguments) {
  foreach ($contexts as $cid => $context) {
    if (empty($context->empty)) {
      continue;
    }

    $new_context = NULL;
    switch ($context->placeholder['type']) {
      case 'relationship':
        $relationship = $context->placeholder['conf'];
        if (isset($contexts[$relationship['context']])) {
          $new_context = ctools_context_get_context_from_relationship($relationship, $contexts[$relationship['context']]);
        }
        break;
      case 'argument':
        if (isset($arguments[$cid]) && $arguments[$cid] !== '') {
          $argument = $context->placeholder['conf'];
          $new_context = ctools_context_get_context_from_argument($argument, $arguments[$cid]);
        }
        break;
      case 'context':
        if (!empty($arguments[$cid])) {
          $context_info = $context->placeholder['conf'];
          $new_context = ctools_context_get_context_from_context($context_info, 'requiredcontext', $arguments[$cid]);
        }
        break;
    }

    if ($new_context && empty($new_context->empty)) {
      $contexts[$cid] = $new_context;
    }
  }

  return $contexts;
}

/**
 * Provide a form array for getting data to replace placeholder contexts
 * with real data.
 */
function ctools_context_replace_form($form, $contexts) {
  foreach ($contexts as $cid => $context) {
    if (empty($context->empty)) {
      continue;
    }

    // Get plugin info from the context which should have been set when the
    // empty context was created.
    $info = NULL;
    $plugin = NULL;
    $settings = NULL;
    switch ($context->placeholder['type']) {
      case 'argument':
        $info = $context->placeholder['conf'];
        $plugin = ctools_get_argument($info['name']);
        break;

      case 'context':
        $info = $context->placeholder['conf'];
        $plugin = ctools_get_context($info['name']);
        break;
    }

    // Ask the plugin where the form is.
    if ($plugin && isset($plugin['placeholder form'])) {
      if (is_array($plugin['placeholder form'])) {
        $form[$cid] = $plugin['placeholder form'];
      }
      else if (function_exists($plugin['placeholder form'])) {
        $widget = $plugin['placeholder form']($info);
        if ($widget) {
          $form[$cid] = $widget;
        }
      }

      if (!empty($form[$cid])) {
        $form[$cid]['#title'] = t('@identifier (@keyword)', array('@keyword' => '%' . $context->keyword, '@identifier' => $context->identifier));
      }
    }
  }
}

// ---------------------------------------------------------------------------
// Functions related to loading access control plugins

/**
 * Fetch metadata on a specific access control plugin.
 *
 * @param $name
 *   Name of a plugin.
 *
 * @return
 *   An array with information about the requested access control plugin.
 */
function ctools_get_access_plugin($name) {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'access', $name);
}

/**
 * Fetch metadata for all access control plugins.
 *
 * @return
 *   An array of arrays with information about all available access control plugins.
 */
function ctools_get_access_plugins() {
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'access');
}

/**
 * Fetch a list of access plugins that are available for a given list of
 * contexts.
 *
 * if 'logged-in-user' is not in the list of contexts, it will be added as
 * this is required.
 */
function ctools_get_relevant_access_plugins($contexts) {
  if (!isset($contexts['logged-in-user'])) {
    $contexts['logged-in-user'] = ctools_access_get_loggedin_context();
  }

  $all_plugins = ctools_get_access_plugins();
  $plugins = array();
  foreach ($all_plugins as $id => $plugin) {
    if (!empty($plugin['required context']) && !ctools_context_match_requirements($contexts, $plugin['required context'])) {
      continue;
    }
    $plugins[$id] = $plugin;
  }

  return $plugins;
}

/**
 * Create a context for the logged in user.
 */
function ctools_access_get_loggedin_context() {
  global $user;
  $context = ctools_context_create('entity:user', $user);
  $context->identifier = t('Logged in user');
  $context->keyword    = 'viewer';
  $context->id         = 0;

  return $context;
}

/**
 * Get a summary of an access plugin's settings.
 */
function ctools_access_summary($plugin, $contexts, $test) {
  if (!isset($contexts['logged-in-user'])) {
    $contexts['logged-in-user'] = ctools_access_get_loggedin_context();
  }

  $description = '';
  if ($function = ctools_plugin_get_function($plugin, 'summary')) {
    $required_context = isset($plugin['required context']) ? $plugin['required context'] : array();
    $context          = isset($test['context']) ? $test['context'] : array();
    $description      = $function($test['settings'], ctools_context_select($contexts, $required_context, $context), $plugin);
  }

  if (!empty($test['not'])) {
    $description = "NOT ($description)";
  }

  return $description;
}

/**
 * Get a summary of a group of access plugin's settings.
 */
function ctools_access_group_summary($access, $contexts) {
  if (empty($access['plugins'])) {
    return;
  }

  $descriptions = array();
  foreach ($access['plugins'] as $id => $test) {
    $plugin = ctools_get_access_plugin($test['name']);
    $descriptions[] = ctools_access_summary($plugin, $contexts, $test);
  }

  $separator = $access['logic'] == 'and' ? t(', and ') : t(', or ');
  return implode($separator, $descriptions);
}

/**
 * Determine if the current user has access via  plugin.
 *
 * @param $settings
 *   An array of settings theoretically set by the user.
 * @param $contexts
 *   An array of zero or more contexts that may be used to determine if
 *   the user has access.
 *
 * @return
 *   TRUE if access is granted, false if otherwise.
 */
function ctools_access($settings, $contexts = array()) {
  if (empty($settings['plugins'])) {
    return TRUE;
  }

  if (!isset($settings['logic'])) {
    $settings['logic'] = 'and';
  }

  if (!isset($contexts['logged-in-user'])) {
    $contexts['logged-in-user'] = ctools_access_get_loggedin_context();
  }

  foreach ($settings['plugins'] as $test) {
    $pass = FALSE;
    $plugin = ctools_get_access_plugin($test['name']);
    if ($plugin && $function = ctools_plugin_get_function($plugin, 'callback')) {
      // Do we need just some contexts or all of them?
      if (!empty($plugin['all contexts'])) {
        $test_contexts = $contexts;
      }
      else {
        $required_context = isset($plugin['required context']) ? $plugin['required context'] : array();
        $context = isset($test['context']) ? $test['context'] : array();
        $test_contexts = ctools_context_select($contexts, $required_context, $context);
      }

      $pass = $function($test['settings'], $test_contexts, $plugin);
      if (!empty($test['not'])) {
        $pass = !$pass;
      }
    }

    if ($pass && $settings['logic'] == 'or') {
      // Pass if 'or' and this rule passed.
      return TRUE;
    }
    else if (!$pass && $settings['logic'] == 'and') {
      // Fail if 'and' and htis rule failed.
      return FALSE;
    }
  }

  // Return TRUE if logic was and, meaning all rules passed.
  // Return FALSE if logic was or, meaning no rule passed.
  return $settings['logic'] == 'and';
}

/**
 * Create default settings for a new access plugin.
 *
 * @param $plugin
 *   The access plugin being used.
 *
 * @return
 *   A default configured test that should be placed in $access['plugins'];
 */
function ctools_access_new_test($plugin) {
  $test = array(
    'name' => $plugin['name'],
    'settings' => array(),
  );

  // Set up required context defaults.
  if (isset($plugin['required context'])) {
    if (is_object($plugin['required context'])) {
      $test['context'] = '';
    }
    else {
      $test['context'] = array();
      foreach ($plugin['required context'] as $required) {
        $test['context'][] = '';
      }
    }
  }


  $default = NULL;
  if (isset($plugin['default'])) {
    $default = $plugin['default'];
  }
  elseif (isset($plugin['defaults'])) {
    $default = $plugin['defaults'];
  }

  // Setup plugin defaults.
  if (isset($default)) {
    if (is_array($default)) {
      $test['settings'] = $default;
    }
    else if (function_exists($default)) {
      $test['settings'] = $default();
    }
    else {
      $test['settings'] = array();
    }
  }

  return $test;
}

/**
 * Apply restrictions to contexts based upon the access control configured.
 *
 * These restrictions allow the UI to not show content that may not
 * be relevant to all types of a particular context.
 */
function ctools_access_add_restrictions($settings, $contexts) {
  if (empty($settings['plugins'])) {
    return;
  }

  if (!isset($settings['logic'])) {
    $settings['logic'] = 'and';
  }

  // We're not going to try to figure out restrictions on the or.
  if ($settings['logic'] == 'or' && count($settings['plugins']) > 1) {
    return;
  }

  foreach ($settings['plugins'] as $test) {
    $plugin = ctools_get_access_plugin($test['name']);
    if ($plugin && $function = ctools_plugin_get_function($plugin, 'restrictions')) {
      $required_context = isset($plugin['required context']) ? $plugin['required context'] : array();
      $context = isset($test['context']) ? $test['context'] : array();
      $contexts = ctools_context_select($contexts, $required_context, $context);
      $function($test['settings'], $contexts);
    }
  }
}
