<?php
// $Id: content.inc,v 1.21 2010/12/31 23:53:34 merlinofchaos Exp $

/**
 * @file
 * Contains the tools to handle pluggable content that can be used by other
 * applications such as Panels or Dashboard.
 *
 * See the context-content.html file in advanced help for documentation
 * of this tool.
 */

/**
 * Provide defaults for a content type.
 *
 * Currently we check for automatically named callbacks to make life a little
 * easier on the developer.
 */
function ctools_content_process(&$plugin, $info) {
  $function_base = $plugin['module'] . '_' . $plugin['name'] . '_content_type_';

  if (empty($plugin['render callback']) && function_exists($function_base . 'render')) {
    $plugin['render callback'] = $function_base . 'render';
  }

  if (empty($plugin['admin title'])) {
    if (function_exists($function_base . 'admin_title')) {
      $plugin['admin title'] = $function_base . 'admin_title';
    }
    else {
      $plugin['admin title'] = $plugin['title'];
    }
  }

  if (empty($plugin['admin info']) && function_exists($function_base . 'admin_info')) {
    $plugin['admin info'] = $function_base . 'admin_info';
  }

  if (!isset($plugin['edit form']) && function_exists($function_base . 'edit_form')) {
    $plugin['edit form'] = $function_base . 'edit_form';
  }

  if (!isset($plugin['add form']) && function_exists($function_base . 'add_form')) {
    $plugin['add form'] = $function_base . 'add_form';
  }

  if (!isset($plugin['add form']) && function_exists($function_base . 'edit_form')) {
    $plugin['add form'] = $function_base . 'edit_form';
  }

  // Another ease of use check:
  if (!isset($plugin['content types'])) {
    // If a subtype plugin exists, try to use it. Otherwise assume single.
    if (function_exists($function_base . 'content_types')) {
      $plugin['content types'] = $function_base . 'content_types';
    }
    else {
      $type = array(
        'title' => $plugin['title'],
        'description' => $plugin['description'],
        'icon' => ctools_content_admin_icon($plugin),
        'category' => $plugin['category'],
      );

      if (isset($plugin['required context'])) {
        $type['required context'] = $plugin['required context'];
      }
      if (isset($plugin['top level'])) {
        $type['top level'] = $plugin['top level'];
      }
      $plugin['content types'] = array($plugin['name'] => $type);
      if (!isset($plugin['single'])) {
        $plugin['single'] = TRUE;
      }
    }
  }
}

/**
 * Fetch metadata on a specific content_type plugin.
 *
 * @param $content type
 *   Name of a panel content type.
 *
 * @return
 *   An array with information about the requested panel content type.
 */
function ctools_get_content_type($content_type) {
  ctools_include('context');
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'content_types', $content_type);
}

/**
 * Fetch metadata for all content_type plugins.
 *
 * @return
 *   An array of arrays with information about all available panel content types.
 */
function ctools_get_content_types() {
  ctools_include('context');
  ctools_include('plugins');
  return ctools_get_plugins('ctools', 'content_types');
}

/**
 * Get all of the individual subtypes provided by a given content type. This
 * would be all of the blocks for the block type, or all of the views for
 * the view type.
 *
 * @param $type
 *   The content type to load.
 *
 * @return
 *   An array of all subtypes available.
 */
function ctools_content_get_subtypes($type) {
  static $cache = array();

  $subtypes = array();

  if (is_array($type)) {
    $plugin = $type;
  }
  else {
    $plugin = ctools_get_content_type($type);
  }

  if (empty($plugin) || empty($plugin['name'])) {
    return;
  }

  if (isset($cache[$plugin['name']])) {
    return $cache[$plugin['name']];
  }

  if (isset($plugin['content types'])) {
    $function = $plugin['content types'];
    if (is_array($function)) {
      $subtypes = $function;
    }
    else if (function_exists($function)) {
      // Cast to array to prevent errors from non-array returns.
      $subtypes = (array) $function($plugin);
    }
  }

  // Walk through the subtypes and ensure minimal settings are
  // retained.
  foreach ($subtypes as $id => $subtype) {
    // Use exact name since this is a modify by reference.
    ctools_content_prepare_subtype($subtypes[$id], $plugin);
  }

  $cache[$plugin['name']] = $subtypes;

  return $subtypes;
}

/**
 * Given a content type and a subtype id, return the information about that
 * content subtype.
 *
 * @param $type
 *   The content type being fetched.
 * @param $subtype_id
 *   The id of the subtype being fetched.
 *
 * @return
 *   An array of information describing the content subtype.
 */
function ctools_content_get_subtype($type, $subtype_id) {
  $subtype = array();
  if (is_array($type)) {
    $plugin = $type;
  }
  else {
    $plugin = ctools_get_content_type($type);
  }

  $function = ctools_plugin_get_function($plugin, 'content type');
  if ($function) {
    $subtype = $function($subtype_id, $plugin);
  }
  else {
    $subtypes = ctools_content_get_subtypes($type);
    if (isset($subtypes[$subtype_id])) {
      $subtype = $subtypes[$subtype_id];
    }
    // If there's only 1 and we somehow have the wrong subtype ID, do not
    // care. Return the proper subtype anyway.
    if (empty($subtype) && !empty($plugin['single'])) {
      $subtype = current($subtypes);
    }
  }

  if ($subtype) {
    ctools_content_prepare_subtype($subtype, $plugin);
  }
  return $subtype;
}

/**
 * Ensure minimal required settings on a content subtype exist.
 */
function ctools_content_prepare_subtype(&$subtype, $plugin) {
  foreach (array('path', 'js', 'css') as $key) {
    if (!isset($subtype[$key]) && isset($plugin[$key])) {
      $subtype[$key] = $plugin[$key];
    }
  }
}

/**
 * Get the content from a given content type.
 *
 * @param $type
 *   The content type. May be the name or an already loaded content type plugin.
 * @param $subtype
 *   The name of the subtype being rendered.
 * @param $conf
 *   The configuration for the content type.
 * @param $keywords
 *   An array of replacement keywords that come from outside contexts.
 * @param $args
 *   The arguments provided to the owner of the content type. Some content may
 *   wish to configure itself based on the arguments the panel or dashboard
 *   received.
 * @param $context
 *   An array of context objects available for use.
 * @param $incoming_content
 *   Any incoming content, if this display is a wrapper.
 *
 * @return
 *   The content as rendered by the plugin. This content should be an array
 *   with the following possible keys:
 *   - title: The safe to render title of the content.
 *   - content: The safe to render HTML content.
 *   - links: An array of links associated with the content suitable for
 *     theme('links').
 *   - more: An optional 'more' link (destination only)
 *   - admin_links: Administrative links associated with the content, suitable
 *     for theme('links').
 *   - feeds: An array of feed icons or links associated with the content.
 *     Each member of the array is rendered HTML.
 *   - type: The content type.
 *   - subtype: The content subtype. These two may be used together as
 *     module-delta for block style rendering.
 */
function ctools_content_render($type, $subtype, $conf, $keywords = array(), $args = array(), $context = array(), $incoming_content = '') {
  if (is_array($type)) {
    $plugin = $type;
  }
  else {
    $plugin = ctools_get_content_type($type);
  }

  $subtype_info = ctools_content_get_subtype($plugin, $subtype);

  $function = ctools_plugin_get_function($subtype_info, 'render callback');
  if (!$function) {
    $function = ctools_plugin_get_function($plugin, 'render callback');
  }

  if ($function) {
    $pane_context = ctools_content_select_context($plugin, $subtype, $conf, $context);
    if ($pane_context === FALSE) {
      return;
    }

    $content = $function($subtype, $conf, $args, $pane_context, $incoming_content);

    // Set up some defaults and other massaging on the content before we hand
    // it back to the caller.
    if (!isset($content->type)) {
      $content->type = $plugin['name'];
    }

    if (!isset($content->subtype)) {
      $content->subtype = $subtype;
    }

    // Override the title if configured to
    if (!empty($conf['override_title'])) {
      // Give previous title as an available substitution here.
      $keywords['%title'] = empty($content->title) ? '' : $content->title;
      $content->original_title = $keywords['%title'];
      $content->title = $conf['override_title_text'];
    }

    if (!empty($content->title)) {
      // Perform substitutions
      if (!empty($keywords) || !empty($context)) {
        $content->title = ctools_context_keyword_substitute($content->title, $keywords, $context);
      }

      // Sterilize the title
      $content->title = filter_xss_admin($content->title);

      // If a link is specified, populate.
      if (!empty($content->title_link)) {
        if (!is_array($content->title_link)) {
          $url = array('href' => $content->title_link);
        }
        else {
          $url = $content->title_link;
        }
        // set defaults so we don't bring up notices
        $url += array('href' => '', 'attributes' => array(), 'query' => array(), 'fragment' => '', 'absolute' => NULL, 'html' => TRUE);
        $content->title = l($content->title, $url['href'], $url);
      }
    }

    return $content;
  }
}

/**
 * Determine if a content type can be edited or not.
 *
 * Some content types simply have their content and no options. This function
 * lets a UI determine if it should display an edit link or not.
 */
function ctools_content_editable($type, $subtype, $conf) {
  if (empty($type['edit form']) && empty($subtype['edit form'])) {
    return FALSE;
  }

  if ($function = ctools_plugin_get_function($subtype, 'check editable')) {
    return $function($type, $subtype, $conf);
  }

  return TRUE;
}

/**
 * Get the administrative title from a given content type.
 *
 * @param $type
 *   The content type. May be the name or an already loaded content type object.
 * @param $subtype
 *   The subtype being rendered.
 * @param $conf
 *   The configuration for the content type.
 * @param $context
 *   An array of context objects available for use. These may be placeholders.
 */
function ctools_content_admin_title($type, $subtype, $conf, $context = NULL) {
  if (is_array($type)) {
    $plugin = $type;
  }
  else if (is_string($type)) {
    $plugin = ctools_get_content_type($type);
  }
  else {
    return;
  }

  if ($function = ctools_plugin_get_function($plugin, 'admin title')) {
    $pane_context = ctools_content_select_context($plugin, $subtype, $conf, $context);
    if ($pane_context === FALSE) {
      if ($plugin['name'] == $subtype) {
        return t('@type will not display due to missing context', array('@type' => $plugin['name']));
      }
      return t('@type:@subtype will not display due to missing context', array('@type' => $plugin['name'], '@subtype' => $subtype));
    }

    return $function($subtype, $conf, $pane_context);
  }
  else if (isset($plugin['admin title'])) {
    return $plugin['admin title'];
  }
  else if (isset($plugin['title'])) {
    return $plugin['title'];
  }
}

/**
 * Get the proper icon path to use, falling back to default icons if no icon exists.
 *
 * $subtype
 *   The loaded subtype info.
 */
function ctools_content_admin_icon($subtype) {
  $icon = '';

  if (isset($subtype['icon'])) {
    $icon = $subtype['icon'];
    if (!file_exists($icon)) {
      $icon = $subtype['path'] . '/' . $icon;
    }
  }

  if (empty($icon) || !file_exists($icon)) {
    $icon = ctools_image_path('no-icon.png');
  }

  return $icon;
}

/**
 * Set up the default $conf for a new instance of a content type.
 */
function ctools_content_get_defaults($plugin, $subtype) {
  if (isset($plugin['defaults'])) {
    $defaults = $plugin['defaults'];
  }
  else if (isset($subtype['defaults'])) {
    $defaults = $subtype['defaults'];
  }
  if (isset($defaults)) {
    if (is_string($defaults) && function_exists($defaults)) {
      if ($return = $defaults($pane)) {
        return $return;
      }
    }
    else if (is_array($defaults)) {
      return $defaults;
    }
  }

  return array();
}

/**
 * Get the administrative title from a given content type.
 *
 * @param $type
 *   The content type. May be the name or an already loaded content type object.
 * @param $subtype
 *   The subtype being rendered.
 * @param $conf
 *   The configuration for the content type.
 * @param $context
 *   An array of context objects available for use. These may be placeholders.
 */
function ctools_content_admin_info($type, $subtype, $conf, $context = NULL) {
  if (is_array($type)) {
    $plugin = $type;
  }
  else {
    $plugin = ctools_get_content_type($type);
  }

  if ($function = ctools_plugin_get_function($plugin, 'admin info')) {
    $output = $function($subtype, $conf, $context);
  }
  if (empty($output) || !is_object($output)) {
    $output = new stdClass();
    $output->title = t('No info');
    $output->content = t('No info available.');
  }
  return $output;
}

/**
 * Add the default FAPI elements to the content type configuration form
 */
function ctools_content_configure_form_defaults($form, &$form_state) {
  $plugin = $form_state['plugin'];
  $subtype = $form_state['subtype'];
  $contexts = isset($form_state['contexts']) ? $form_state['contexts'] : NULL;
  $conf = $form_state['conf'];

  $add_submit = FALSE;
  if (!empty($subtype['required context']) && is_array($contexts)) {
    $form['context'] = ctools_context_selector($contexts, $subtype['required context'], isset($conf['context']) ? $conf['context'] : array());
    $add_submit = TRUE;
  }

  ctools_include('dependent');

  // Unless we're not allowed to override the title on this content type, add this
  // gadget to all panes.
  if (empty($plugin['no title override']) && empty($subtype['no title override'])) {
    $form['aligner_start'] = array(
      '#markup' => '<div class="option-text-aligner clearfix">',
    );
    $form['override_title'] = array(
      '#type' => 'checkbox',
      '#default_value' => isset($conf['override_title']) ? $conf['override_title'] : '',
      '#title' => t('Override title'),
      '#id' => 'override-title-checkbox',
    );
    $form['override_title_text'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($conf['override_title_text']) ? $conf['override_title_text'] : '',
      '#size' => 35,
      '#id' => 'override-title-textfield',
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('override-title-checkbox' => array(1)),
      '#dependency_type' => 'disable',
    );
    $form['aligner_stop'] = array(
      '#markup' => '</div>',
    );
    if (is_array($contexts)) {
      $form['override_title_markup'] = array(
        '#prefix' => '<div class="description">',
        '#suffix' => '</div>',
        '#markup' => t('You may use %keywords from contexts, as well as %title to contain the original title.'),
      );
    }
    $add_submit = TRUE;
  }

  if ($add_submit) {
    // '#submit' is already set up due to the wizard.
    $form['#submit'][] = 'ctools_content_configure_form_defaults_submit';
  }
  return $form;
}

/**
 * Submit handler to store context/title override info.
 */
function ctools_content_configure_form_defaults_submit(&$form, &$form_state) {
  if (isset($form_state['values']['context'])) {
    $form_state['conf']['context'] = $form_state['values']['context'];
  }
  if (isset($form_state['values']['override_title'])) {
    $form_state['conf']['override_title'] = $form_state['values']['override_title'];
    $form_state['conf']['override_title_text'] = $form_state['values']['override_title_text'];
  }
}

/**
 * Get the config form.
 *
 * The $form_info and $form_state need to be preconfigured with data you'll need
 * such as whether or not you're using ajax, or the modal. $form_info will need
 * your next/submit callbacks so that you can cache your data appropriately.
 *
 * @return
 *   If this function returns false, no form exists.
 */
function ctools_content_form($op, $form_info, &$form_state, $plugin, $subtype_name, $subtype, &$conf, $step = NULL) {
  $form_state += array(
    'plugin' => $plugin,
    'subtype' => $subtype,
    'subtype_name' => $subtype_name,
    'conf' => &$conf,
    'op' => $op,
  );

  $form_info += array(
    'id' => 'ctools_content_form',
    'show back' => TRUE,
  );

  // Turn the forms defined in the plugin into the format the wizard needs.
  if ($op == 'add') {
    if (!empty($subtype['add form'])) {
      _ctools_content_create_form_info($form_info, $subtype['add form'], $subtype, $subtype, $op);
    }
    else if (!empty($plugin['add form'])) {
      _ctools_content_create_form_info($form_info, $plugin['add form'], $plugin, $subtype, $op);
    }
  }

  if (empty($form_info['order'])) {
    // Use the edit form for the add form if add form was completely left off.
    if (!empty($subtype['edit form'])) {
      _ctools_content_create_form_info($form_info, $subtype['edit form'], $subtype, $subtype, $op);
    }
    else if (!empty($plugin['edit form'])) {
      _ctools_content_create_form_info($form_info, $plugin['edit form'], $plugin, $subtype, $op);
    }
  }

  if (empty($form_info['order'])) {
    return FALSE;
  }

  ctools_include('wizard');
  return ctools_wizard_multistep_form($form_info, $step, $form_state);

}

function _ctools_content_create_form_info(&$form_info, $info, $plugin, $subtype, $op) {
  if (is_string($info)) {
    if (empty($subtype['title'])) {
      $title = t('Configure');
    }
    else if ($op == 'add') {
      $title = t('Configure new !subtype_title', array('!subtype_title' => $subtype['title']));
    }
    else {
      $title = t('Configure !subtype_title', array('!subtype_title' => $subtype['title']));
    }
    $form_info['order'] = array('form' => $title);
    $form_info['forms'] = array(
      'form' => array(
        'title' => $title,
        'form id' => $info,
        'wrapper' => 'ctools_content_configure_form_defaults',
      ),
    );
  }
  else if (is_array($info)) {
    $form_info['order'] = array();
    $form_info['forms'] = array();
    $count = 0;
    $base = 'step';
    $wrapper = NULL;
    foreach ($info as $form_id => $title) {
      // @todo -- docs say %title can be used to sub for the admin title.
      $step = $base . ++$count;
      if (empty($wrapper)) {
        $wrapper = $step;
      }

      if (is_array($title)) {
        if (!empty($title['default'])) {
          $wrapper = $step;
        }
        $title = $title['title'];
      }

      $form_info['order'][$step] = $title;
      $form_info['forms'][$step] = array(
        'title' => $title,
        'form id' => $form_id,
      );
    }
    if ($wrapper) {
      $form_info['forms'][$wrapper]['wrapper'] = 'ctools_content_configure_form_defaults';
    }
  }
}

/**
 * Get an array of all available content types that can be fed into the
 * display editor for the add content list.
 *
 * @param $context
 *   If a context is provided, content that requires that context can apepar.
 * @param $has_content
 *   Whether or not the display will have incoming content
 * @param $allowed_types
 *   An array of allowed content types (pane types) keyed by content_type . '-' . sub_type
 * @param $default_types
 *   A default allowed/denied status for content that isn't known about
 */
function ctools_content_get_available_types($contexts = NULL, $has_content = FALSE, $allowed_types = NULL, $default_types = NULL) {
  $plugins = ctools_get_content_types();
  $available = array();

  foreach ($plugins as $id => $plugin) {
    foreach (ctools_content_get_subtypes($plugin) as $subtype_id => $subtype) {
      // exclude items that require content if we're saying we don't
      // provide it.
      if (!empty($subtype['requires content']) && !$has_content) {
        continue;
      }

      // Check to see if the content type can be used in this context.
      if (!empty($subtype['required context'])) {
        if (!ctools_context_match_requirements($contexts, $subtype['required context'])) {
          continue;
        }
      }

      // Check to see if the passed-in allowed types allows this content.
      if ($allowed_types) {
        $key = $id . '-' . $subtype_id;
        if (!isset($allowed_types[$key])) {
          $allowed_types[$key] = isset($default_types[$id]) ? $default_types[$id] : $default_types['other'];
        }
        if (!$allowed_types[$key]) {
          continue;
        }
      }

      // If we made it through all the tests, then we can use this content.
      $available[$id][$subtype_id] = $subtype;
    }
  }
  return $available;
}

/**
 * Get an array of all content types that can be fed into the
 * display editor for the add content list, regardless of
 * availability.
 *
 */
function ctools_content_get_all_types() {
  $plugins = ctools_get_content_types();
  $available = array();

  foreach ($plugins as $id => $plugin) {
    foreach (ctools_content_get_subtypes($plugin) as $subtype_id => $subtype) {
      // If we made it through all the tests, then we can use this content.
      $available[$id][$subtype_id] = $subtype;
    }
  }
  return $available;
}

/**
 * Select the context to be used for a piece of content, based upon config.
 *
 * @param $plugin
 *   The content plugin
 * @param $subtype
 *   The subtype of the content.
 * @param $conf
 *   The configuration array that should contain the context.
 * @param $contexts
 *   A keyed array of available contexts.
 *
 * @return
 *   The matching contexts or NULL if none or necessary, or FALSE if
 *   requirements can't be met.
 */
function ctools_content_select_context($plugin, $subtype, $conf, $contexts) {
  // Identify which of our possible contexts apply.
  if (empty($subtype)) {
    return;
  }

  $subtype_info = ctools_content_get_subtype($plugin, $subtype);
  if (empty($subtype_info)) {
    return;
  }

  if (!empty($subtype_info['all contexts']) || !empty($plugin['all contexts'])) {
    return $contexts;
  }

  // If the content requires a context, fetch it; if no context is returned,
  // do not display the pane.
  if (empty($subtype_info['required context'])) {
    return;
  }

  // Deal with dynamic required contexts not getting updated in the panes.
  // For example, Views let you dynamically change context info. While
  // we cannot be perfect, one thing we can do is if no context at all
  // was asked for, and then was later added but none is selected, make
  // a best guess as to what context should be used. THis is right more
  // than it's wrong.
  if (is_array($subtype_info['required context'])) {
    if (empty($conf['context']) || count($subtype_info['required context']) != count($conf['context'])) {
      foreach($subtype_info['required context'] as $index => $required) {
        if (!isset($conf['context'][$index])) {
          $filtered = ctools_context_filter($contexts, $required);
          if ($filtered) {
            $keys = array_keys($filtered);
            $conf['context'][$index] = array_shift($keys);
          }
        }
      }
    }
  }
  else {
    if (empty($conf['context'])) {
      $filtered = ctools_context_filter($contexts, $subtype_info['required context']);
      if ($filtered) {
        $keys = array_keys($filtered);
        $conf['context'] = array_shift($keys);
      }
    }
  }

  if (empty($conf['context'])) {
    return;
  }

  $context = ctools_context_select($contexts, $subtype_info['required context'], $conf['context']);

  return $context;
}
