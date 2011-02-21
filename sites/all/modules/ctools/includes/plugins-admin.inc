<?php
// $Id: plugins-admin.inc,v 1.3 2011/01/05 22:57:26 merlinofchaos Exp $

/**
 * @file
 * Contains generic plugin administration functions.
 *
 * CTools includes the ability to (relatively) easily provide wizard based
 * configuration for plugins, meaning plugins that need configuration can
 * automatically allow multi-step forms.
 *
 * Implementing this
 */
/**
 * Get a plugin configuration form.
 *
 * The $form_info and $form_state need to be preconfigured with data you'll need
 * such as whether or not you're using ajax, or the modal. $form_info will need
 * your next/submit callbacks so that you can cache your data appropriately.
 *
 * @param array $form_info
 *   This form_info *must* contain at least the path. next and finish callbacks
 *   are advisable but not necessary if using wizard's auto caching. Setting
 *   a cache mechanism is advisable. If not using auto caching, next and finish
 *   callbacks will be necessary.
 *
 *   Also be sure to set up things such as AJAX settings and any temporary
 *   data you will need. Simply setting 'modal' => TRUE and
 *   'modal return' => TRUE should make this system work well in the modal.
 *
 *   In addition to standard wizard fields, this supports one extra field:
 *   - 'default form': A callback to a 'wrapper' that will be applied to either
 *     the first or a marked form. This is useful for adding global features that
 *     are applicable to all instances of a plugin, such as identifiers, or
 *     contexts, or the like.
 *
 * @param array &$form_state
 *   This is a standard form state array. This system imposes some requirements
 *   on what will be in the form state:
 *
 *   - 'plugin': The plugin definition being edited.
 *   - 'conf': The configuration that is being edited, presumed to be an array.
 *             Ultimately at the end, this is where the modified config will be
 *             found.
 *   - 'op': The operation, either 'add' or 'edit'. This is used to derive form
 *           names and can also be used to derive default values from the plugin.
 *   - 'step': The current step. May be null if it is the 'first' step, but
 *             generally needs to be defined in the path.
 *
 * @param string $default_form
 *   An optional default form that can be added.
 *
 * @return
 *   If this function returns false, no form exists.
 */
function ctools_plugin_configure_form($form_info, &$form_state) {
  // Turn the forms defined in the plugin into the format the wizard needs.
  _ctools_plugin_configure_create_form_info($form_info, $form_state['plugin'], $form_state['op']);

  if (empty($form_info['order'])) {
    return FALSE;
  }

  ctools_include('wizard');
  return ctools_wizard_multistep_form($form_info, $form_state['step'], $form_state);
}

function _ctools_plugin_configure_create_form_info(&$form_info, $plugin_definition, $op) {
  // Provide a few extra defaults.
  $form_info += array(
    'id' => 'ctools_plugin_configure_form',
    'show back' => TRUE,
  );

  // Figure out what the forms should actually be. Since they can be specified
  // in a couple of different ways (in order to support simple declarations for
  // the minimal forms but more complex declarations for powerful wizards).
  if ($op == 'add') {
    if (!empty($plugin_definition['add form'])) {
      $info = $plugin_definition['add form'];
    }
  }
  if (!isset($info) || $op == 'edit') {
    // Use the edit form for the add form if add form was completely left off.
    if (!empty($plugin_definition['edit form'])) {
      $info = $plugin_definition['edit form'];
    }
  }

  // If there is a default form wrapper, but no form is supplied,
  // use the wrapper as the form.
  if (empty($info) && !empty($form_info['default form'])) {
    $info = $form_info['default form'];
  }

  // @todo we may want to make these titles more settable?
  if (is_string($info)) {
    if (empty($plugin_definition['title'])) {
      $title = t('Configure');
    }
    else if ($op == 'add') {
      $title = t('Configure new !plugin_title', array('!plugin_title' => $plugin_definition['title']));
    }
    else {
      $title = t('Configure !plugin_title', array('!plugin_title' => $plugin_definition['title']));
    }
    $form_info['order'] = array('form' => $title);
    $form_info['forms'] = array(
      'form' => array(
        'title' => $title,
        'form id' => $info,
      ),
    );

    // Add the default form if one is specified.
    if (!empty($form_info['default form']) && $form_info['forms']['form']['form id'] != $form_info['default form']) {
      $form_info['forms']['form']['wrapper'] = $form_info['default form'];
    }

    // If no submit is supplied, supply the default submit which will do the
    // most obvious task.
    if (!function_exists($form_info['forms']['form']['form id'] . '_submit')) {
      // Store the original wrapper so we can chain it.
      if (!empty($form_info['forms']['form']['wrapper'])) {
        $form_info['forms']['form']['original wrapper'] = $form_info['forms']['form']['wrapper'];
      }
      $form_info['forms']['form']['wrapper'] = 'ctools_plugins_default_form_wrapper';
    }
  }
  else if (is_array($info)) {
    $form_info['order'] = array();
    $form_info['forms'] = array();
    $count = 0;
    $base = 'step';
    $wrapper = NULL;
    foreach ($info as $form_id => $title) {
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
    if ($wrapper && !empty($form_info['default form'])) {
      $form_info['forms'][$wrapper]['wrapper'] = $form_info['default form'];
    }
  }
}

/**
 * A wrapper to provide a default submit so that plugins don't have to duplicate
 * a whole bunch of code to do what most of them want to do anyway.
 */
function ctools_plugins_default_form_wrapper($form, &$form_state) {
  $form_info = &$form_state['form_info'];
  $info = $form_info['forms'][$form_state['step']];

  if (isset($info['original wrapper']) && function_exists($info['original wrapper'])) {
    $form = $info['original wrapper']($form, $form_state);
  }

  if (isset($form['buttons']['next'])) {
    if (empty($form['buttons']['next']['#submit'])) {
      $form['buttons']['next']['#submit'] = $form['#submit'];
    }
    $form['buttons']['next']['#submit'][] = 'ctools_plugins_default_form_wrapper_submit';
  }
  if (isset($form['buttons']['return'])) {
    if (empty($form['buttons']['return']['#submit'])) {
      $form['buttons']['return']['#submit'] = $form['#submit'];
    }
    $form['buttons']['return']['#submit'][] = 'ctools_plugins_default_form_wrapper_submit';
  }
  return $form;
}

/**
 * Provide a default storage mechanism.
 */
function ctools_plugins_default_form_wrapper_submit(&$form, &$form_state) {
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    if (isset($form_state['values'][$key])) {
      $form_state['conf'][$key] = $form_state['values'][$key];
    }
  }
}
