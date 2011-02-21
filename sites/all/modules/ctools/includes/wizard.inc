<?php
// $Id: wizard.inc,v 1.20 2010/10/27 20:07:10 merlinofchaos Exp $

/**
 * @file
 * CTools' multi-step form wizard tool.
 *
 * This tool enables the creation of multi-step forms that go from one
 * form to another. The forms themselves can allow branching if they
 * like, and there are a number of configurable options to how
 * the wizard operates.
 *
 * The wizard can also be friendly to ajax forms, such as when used
 * with the modal tool.
 *
 * The wizard provides callbacks throughout the process, allowing the
 * owner to control the flow. The general flow of what happens is:
 *
 * Generate a form
 * submit a form
 * based upon button clicked, 'finished', 'next form', 'cancel' or 'return'.
 *
 * Each action has its own callback, so cached objects can be modifed and or
 * turned into real objects. Each callback can make decisions about where to
 * go next if it wishes to override the default flow.
 */

/**
 * Display a multi-step form.
 *
 * Aside from the addition of the $form_info which contains an array of
 * information and configuration so the multi-step wizard can do its thing,
 * this function works a lot like drupal_build_form.
 *
 * Remember that the form builders for this form will receive
 * &$form, &$form_state, NOT just &$form_state and no additional args.
 *
 * Do NOT use #required => TRUE with these forms as that validation
 * cannot be skipped for the CANCEL button.
 *
 * @param $form_info
 *   An array of form info. @todo document the array.
 * @param $step
 *   The current form step.
 * @param &$form_state
 *   The form state array; this is a reference so the caller can get back
 *   whatever information the form(s) involved left for it.
 */
function ctools_wizard_multistep_form($form_info, $step, &$form_state) {
  // allow order array to be optional
  if (empty($form_info['order'])) {
    foreach($form_info['forms'] as $step_id => $params) {
      $form_info['order'][$step_id] = $params['title'];
    }
  }

  if (!isset($step)) {
    $keys = array_keys($form_info['order']);
    $step = array_shift($keys);
  }

  ctools_wizard_defaults($form_info);

  // If automated caching is enabled, ensure that everything is as it
  // should be.
  if (!empty($form_info['auto cache'])) {
    // If the cache mechanism hasn't been set, default to the simple
    // mechanism and use the wizard ID to ensure uniqueness so cache
    // objects don't stomp on each other.
    if (!isset($form_info['cache mechanism'])) {
      $form_info['cache mechanism'] = 'simple::wizard::' . $form_info['id'];
    }

    // If not set, default the cache key to the wizard ID. This is often
    // a unique ID of the object being edited or similar.
    if (!isset($form_info['cache key'])) {
      $form_info['cache key'] = $form_info['id'];
    }

    // If not set, default the cache location to storage. This is often
    // somnething like 'conf'.
    if (!isset($form_info['cache location'])) {
      $form_info['cache location'] = 'storage';
    }

    // If absolutely nothing was set for the cache area to work on
    if (!isset($form_state[$form_info['cache location']])) {
      ctools_include('cache');
      $form_state[$form_info['cache location']] = ctools_cache_get($form_info['cache mechanism'], $form_info['cache key']);
    }
  }

  $form_state['step'] = $step;
  $form_state['form_info'] = $form_info;

  // Ensure we have form information for the current step.
  if (!isset($form_info['forms'][$step])) {
    return;
  }

  // Ensure that whatever include file(s) were requested by the form info are
  // actually included.
  $info = $form_info['forms'][$step];

  if (!empty($info['include'])) {
    if (is_array($info['include'])) {
      foreach ($info['include'] as $file) {
        require_once DRUPAL_ROOT . '/' . $file;
      }
    }
    else {
      require_once DRUPAL_ROOT . '/' . $info['include'];
    }
  }

  // This tells drupal_build_form to apply our wrapper to the form. It
  // will give it buttons and the like.
  $form_state['wrapper_callback'] = 'ctools_wizard_wrapper';
  if (!isset($form_state['rerender'])) {
    $form_state['rerender'] = FALSE;
  }

  $form_state['no_redirect'] = TRUE;

  $output = drupal_build_form($info['form id'], $form_state);

  if (empty($form_state['executed']) || !empty($form_state['rerender'])) {
    if (empty($form_state['title']) && !empty($info['title'])) {
      $form_state['title'] = $info['title'];
    }

    if (!empty($form_state['ajax render'])) {
      // Any include files should already be included by this point:
      return $form_state['ajax render']($form_state, $output);
    }

    // Automatically use the modal tool if set to true.
    if (!empty($form_state['modal']) && empty($form_state['modal return'])) {
      ctools_include('modal');

      // This overwrites any previous commands.
      $form_state['commands'] = ctools_modal_form_render($form_state, $output);
    }
  }

  if (!empty($form_state['executed'])) {
    // We use the plugins get_function format because it's powerful and
    // not limited to just functions.
    ctools_include('plugins');

    if (isset($form_state['clicked_button']['#wizard type'])) {
      $type = $form_state['clicked_button']['#wizard type'];
      // If we have a callback depending upon the type of button that was
      // clicked, call it.
      if ($function = ctools_plugin_get_function($form_info, "$type callback")) {
        $function($form_state);
      }

      // If auto-caching is on, we need to write the cache on next and
      // clear the cache on finish.
      if (!empty($form_info['auto cache'])) {
        if ($type == 'next') {
          ctools_include('cache');
          ctools_cache_set($form_info['cache mechanism'], $form_info['cache key'], $form_state[$form_info['cache location']]);
        }
        elseif ($type == 'finish') {
          ctools_include('cache');
          ctools_cache_clear($form_info['cache mechanism'], $form_info['cache key']);
        }
      }

      // Set a couple of niceties:
      if ($type == 'finish') {
        $form_state['complete'] = TRUE;
      }

      if ($type == 'cancel') {
        $form_state['cancel'] = TRUE;
      }

      // If the modal is in use, some special code for it:
      if (!empty($form_state['modal']) && empty($form_state['modal return'])) {
        if ($type != 'next') {
          // Automatically dismiss the modal if we're not going to another form.
          ctools_include('modal');
          $form_state['commands'][] = ctools_modal_command_dismiss();
        }
      }
    }

    if (empty($form_state['ajax'])) {
      // redirect, if one is set.
      if ($form_state['redirect']) {
        if (is_array($form_state['redirect'])) {
          call_user_func_array('drupal_goto', $form_state['redirect']);
        }
        else {
          drupal_goto($form_state['redirect']);
        }
      }
    }
    else if (isset($form_state['ajax next'])) {
      // Clear a few items off the form state so we don't double post:
      $next = $form_state['ajax next'];
      unset($form_state['ajax next']);
      unset($form_state['executed']);
      unset($form_state['post']);
      unset($form_state['next']);
      return ctools_wizard_multistep_form($form_info, $next, $form_state);
    }

    // If the callbacks wanted to do something besides go to the next form,
    // it needs to have set $form_state['commands'] with something that can
    // be rendered.
  }

  // Render ajax commands if we have any.
  if (isset($form_state['ajax']) && isset($form_state['commands']) && empty($form_state['modal return'])) {
    return ajax_render($form_state['commands']);
  }

  // Otherwise, return the output.
  return $output;
}

/**
 * Provide a wrapper around another form for adding multi-step information.
 */
function ctools_wizard_wrapper($form, &$form_state) {
  $form_info = &$form_state['form_info'];
  $info = $form_info['forms'][$form_state['step']];

  // Determine the next form from this step.
  // Create a form trail if we're supposed to have one.
  $trail = array();
  $previous = TRUE;
  foreach ($form_info['order'] as $id => $title) {
    if ($id == $form_state['step']) {
      $previous = FALSE;
      $class = 'wizard-trail-current';
    }
    elseif ($previous) {
      $not_first = TRUE;
      $class = 'wizard-trail-previous';
      $form_state['previous'] = $id;
    }
    else {
      $class = 'wizard-trail-next';
      if (!isset($form_state['next'])) {
        $form_state['next'] = $id;
      }
      if (empty($form_info['show trail'])) {
        break;
      }
    }

    if (!empty($form_info['show trail'])) {
      if (!empty($form_info['free trail'])) {
        // ctools_wizard_get_path() returns results suitable for #redirect
        // which can only be directly used in drupal_goto. We have to futz
        // with it.
        $path = ctools_wizard_get_path($form_info, $id);
        $options = array();
        if (!empty($path[1])) {
          $options['query'] = $path[1];
        }
        if (!empty($path[2])) {
          $options['fragment'] = $path[2];
        }
        $title = l($title, $path[0], $options);
      }
      $trail[] = '<span class="' . $class . '">' . $title . '</span>';
    }
  }

  // Display the trail if instructed to do so.
  if (!empty($form_info['show trail'])) {
    ctools_add_css('wizard');
    $form['ctools_trail'] = array(
      '#markup' => theme(array('ctools_wizard_trail__' . $form_info['id'], 'ctools_wizard_trail'), array('trail' => $trail)),
      '#weight' => -1000,
    );
  }

  if (empty($form_info['no buttons'])) {
    // Ensure buttons stay on the bottom.
    $form['buttons'] = array(
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
      '#weight' => 1000,
    );

    $button_attributes = array();
    if (!empty($form_state['ajax']) && empty($form_state['modal'])) {
      $button_attributes = array('class' => array('ctools-use-ajax'));
    }

    if (!empty($form_info['show back']) && isset($form_state['previous'])) {
      $form['buttons']['previous'] = array(
        '#type' => 'submit',
        '#value' => $form_info['back text'],
        '#next' => $form_state['previous'],
        '#wizard type' => 'next',
        '#weight' => -2000,
        '#limit_validation_errors' => array(),
        // hardcode the submit so that it doesn't try to save data.
        '#submit' => array('ctools_wizard_submit'),
        '#attributes' => $button_attributes,
      );

      if (isset($form_info['no back validate']) || isset($info['no back validate'])) {
        $form['buttons']['previous']['#validate'] = array();
      }
    }

    // If there is a next form, place the next button.
    if (isset($form_state['next']) || !empty($form_info['free trail'])) {
      $form['buttons']['next'] = array(
        '#type' => 'submit',
        '#value' => $form_info['next text'],
        '#next' => !empty($form_info['free trail']) ? $form_state['step'] : $form_state['next'],
        '#wizard type' => 'next',
        '#weight' => -1000,
        '#attributes' => $button_attributes,
      );
    }

    // There are two ways the return button can appear. If this is not the
    // end of the form list (i.e, there is a next) then it's "update and return"
    // to be clear. If this is the end of the path and there is no next, we
    // call it 'Finish'.

    // Even if there is no direct return path (some forms may not want you
    // leaving in the middle) the final button is always a Finish and it does
    // whatever the return action is.
    if (!empty($form_info['show return']) && !empty($form_state['next'])) {
      $form['buttons']['return'] = array(
        '#type' => 'submit',
        '#value' =>  $form_info['return text'],
        '#wizard type' => 'return',
        '#attributes' => $button_attributes,
      );
    }
    else if (empty($form_state['next']) || !empty($form_info['free trail'])) {
      $form['buttons']['return'] = array(
        '#type' => 'submit',
        '#value' => $form_info['finish text'],
        '#wizard type' => 'finish',
        '#attributes' => $button_attributes,
      );
    }

    // If we are allowed to cancel, place a cancel button.
    if ((isset($form_info['cancel path']) && !isset($form_info['show cancel'])) || !empty($form_info['show cancel'])) {
      $form['buttons']['cancel'] = array(
        '#type' => 'submit',
        '#value' => $form_info['cancel text'],
        '#wizard type' => 'cancel',
        // hardcode the submit so that it doesn't try to save data.
        '#limit_validation_errors' => array(),
        '#submit' => array('ctools_wizard_submit'),
        '#attributes' => $button_attributes,
      );
    }

    // Set up optional validate handlers.
    $form['#validate'] = array();
    if (function_exists($info['form id'] . '_validate')) {
      $form['#validate'][] = $info['form id'] . '_validate';
    }
    if (isset($info['validate']) && function_exists($info['validate'])) {
      $form['#validate'][] = $info['validate'];
    }

    // Set up our submit handler after theirs. Since putting something here will
    // skip Drupal's autodetect, we autodetect for it.

    // We make sure ours is after theirs so that they get to change #next if
    // the want to.
    $form['#submit'] = array();
    if (function_exists($info['form id'] . '_submit')) {
      $form['#submit'][] = $info['form id'] . '_submit';
    }
    if (isset($info['submit']) && function_exists($info['submit'])) {
      $form['#submit'][] = $info['submit'];
    }
    $form['#submit'][] = 'ctools_wizard_submit';
  }

  if (!empty($form_state['ajax'])) {
    $params = ctools_wizard_get_path($form_state['form_info'], $form_state['step']);
    if (count($params) > 1) {
      $url = array_shift($params);
      $options = array();

      $keys = array(0 => 'query', 1 => 'fragment');
      foreach ($params as $key => $value) {
        if (isset($keys[$key]) && isset($value)) {
          $options[$keys[$key]] = $value;
        }
      }

      $params = array($url, $options);
    }
    $form['#action'] =  call_user_func_array('url', $params);
  }

  if (isset($info['wrapper']) && function_exists($info['wrapper'])) {
    $form = $info['wrapper']($form, $form_state);
  }

  if (isset($form_info['wrapper']) && function_exists($form_info['wrapper'])) {
    $form = $form_info['wrapper']($form, $form_state);
  }
  return $form;
}

/**
 * On a submit, go to the next form.
 */
function ctools_wizard_submit(&$form, &$form_state) {
  if (isset($form_state['clicked_button']['#wizard type'])) {
    $type = $form_state['clicked_button']['#wizard type'];

    // if AJAX enabled, we proceed slightly differently here.
    if (!empty($form_state['ajax'])) {
      if ($type == 'next') {
        $form_state['ajax next'] = $form_state['clicked_button']['#next'];
      }
    }
    else {
      if ($type == 'cancel' && isset($form_state['form_info']['cancel path'])) {
        $form_state['redirect'] = $form_state['form_info']['cancel path'];
      }
      else if ($type == 'next') {
        $form_state['redirect'] = ctools_wizard_get_path($form_state['form_info'], $form_state['clicked_button']['#next']);
      }
      else if (isset($form_state['form_info']['return path'])) {
        $form_state['redirect'] = $form_state['form_info']['return path'];
      }
      else if ($type == 'finish' && isset($form_state['form_info']['cancel path'])) {
        $form_state['redirect'] = $form_state['form_info']['cancel path'];
      }
    }
  }
}

/**
 * Create a path from the form info and a given step.
 */
function ctools_wizard_get_path($form_info, $step) {
  if (is_array($form_info['path'])) {
    foreach ($form_info['path'] as $id => $part) {
      $form_info['path'][$id] = str_replace('%step', $step, $form_info['path'][$id]);
    }
    return $form_info['path'];
  }
  else {
    return array(str_replace('%step', $step, $form_info['path']));
  }
}

/**
 * Set default parameters and callbacks if none are given.
 * Callbacks follows pattern:
 * $form_info['id']_$hook
 * $form_info['id']_$form_info['forms'][$step_key]_$hook
 */
function ctools_wizard_defaults(&$form_info) {
  $hook = $form_info['id'];
  $defaults = array(
    'show trail' => FALSE,
    'free trail' => FALSE,
    'show back' => FALSE,
    'show cancel' => FALSE,
    'show return' => FALSE,
    'next text' => t('Continue'),
    'back text' => t('Back'),
    'return text' => t('Update and return'),
    'finish text' => t('Finish'),
    'cancel text' => t('Cancel'),
  );

  if (!empty($form_info['free trail'])) {
    $defaults['next text'] = t('Update');
    $defaults['finish text'] = t('Save');
  }

  $form_info = $form_info + $defaults;
  // set form callbacks if they aren't defined
  foreach($form_info['forms'] as $step => $params) {
    if (!$params['form id']) {
       $form_callback = $hook . '_' . $step . '_form';
       $form_info['forms'][$step]['form id'] = $form_callback;
    }
  }

  // set button callbacks
  $callbacks = array(
    'back callback' => '_back',
    'next callback' => '_next',
    'return callback' => '_return',
    'cancel callback' => '_cancel',
    'finish callback' => '_finish',
  );

  foreach($callbacks as $key => $callback) {
    // never overwrite if explicity defined
    if (empty($form_info[$key])) {
      $wizard_callback = $hook . $callback;
      if (function_exists($wizard_callback))  {
        $form_info[$key] = $wizard_callback;
      }
    }
  }
}
