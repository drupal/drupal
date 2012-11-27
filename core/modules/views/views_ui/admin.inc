<?php

/**
 * @file
 * Provides the Views' administrative interface.
 */

use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views_ui\ViewUI;
use Drupal\views_ui\ViewFormControllerBase;
use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\wizard\WizardException;

/**
 * Returns the results of the live preview.
 */
function views_ui_preview(ViewUI $view, $display_id) {
  // Pass along any other arguments.
  $args = func_get_args();
  unset($args[0], $args[1]);
  return $view->renderPreview($display_id, $args);
}

/**
 * Page callback to add a new view.
 */
function views_ui_add_page() {
  drupal_set_title(t('Add new view'));
  $view = entity_create('view', array());
  return entity_get_form($view, 'add');
}

/**
 * Converts a form element in the add view wizard to be AJAX-enabled.
 *
 * This function takes a form element and adds AJAX behaviors to it such that
 * changing it triggers another part of the form to update automatically. It
 * also adds a submit button to the form that appears next to the triggering
 * element and that duplicates its functionality for users who do not have
 * JavaScript enabled (the button is automatically hidden for users who do have
 * JavaScript).
 *
 * To use this function, call it directly from your form builder function
 * immediately after you have defined the form element that will serve as the
 * JavaScript trigger. Calling it elsewhere (such as in hook_form_alter()) may
 * mean that the non-JavaScript fallback button does not appear in the correct
 * place in the form.
 *
 * @param $wrapping_element
 *   The element whose child will server as the AJAX trigger. For example, if
 *   $form['some_wrapper']['triggering_element'] represents the element which
 *   will trigger the AJAX behavior, you would pass $form['some_wrapper'] for
 *   this parameter.
 * @param $trigger_key
 *   The key within the wrapping element that identifies which of its children
 *   serves as the AJAX trigger. In the above example, you would pass
 *   'triggering_element' for this parameter.
 * @param $refresh_parents
 *   An array of parent keys that point to the part of the form that will be
 *   refreshed by AJAX. For example, if triggering the AJAX behavior should
 *   cause $form['dynamic_content']['section'] to be refreshed, you would pass
 *   array('dynamic_content', 'section') for this parameter.
 */
function views_ui_add_ajax_trigger(&$wrapping_element, $trigger_key, $refresh_parents) {
  $seen_ids = &drupal_static(__FUNCTION__ . ':seen_ids', array());
  $seen_buttons = &drupal_static(__FUNCTION__ . ':seen_buttons', array());

  // Add the AJAX behavior to the triggering element.
  $triggering_element = &$wrapping_element[$trigger_key];
  $triggering_element['#ajax']['callback'] = 'views_ui_ajax_update_form';
  // We do not use drupal_html_id() to get an ID for the AJAX wrapper, because
  // it remembers IDs across AJAX requests (and won't reuse them), but in our
  // case we need to use the same ID from request to request so that the
  // wrapper can be recognized by the AJAX system and its content can be
  // dynamically updated. So instead, we will keep track of duplicate IDs
  // (within a single request) on our own, later in this function.
  $triggering_element['#ajax']['wrapper'] = 'edit-view-' . implode('-', $refresh_parents) . '-wrapper';

  // Add a submit button for users who do not have JavaScript enabled. It
  // should be displayed next to the triggering element on the form.
  $button_key = $trigger_key . '_trigger_update';
  $wrapping_element[$button_key] = array(
    '#type' => 'submit',
    // Hide this button when JavaScript is enabled.
    '#attributes' => array('class' => array('js-hide')),
    '#submit' => array('views_ui_nojs_submit'),
    // Add a process function to limit this button's validation errors to the
    // triggering element only. We have to do this in #process since until the
    // form API has added the #parents property to the triggering element for
    // us, we don't have any (easy) way to find out where its submitted values
    // will eventually appear in $form_state['values'].
    '#process' => array_merge(array('views_ui_add_limited_validation'), element_info_property('submit', '#process', array())),
    // Add an after-build function that inserts a wrapper around the region of
    // the form that needs to be refreshed by AJAX (so that the AJAX system can
    // detect and dynamically update it). This is done in #after_build because
    // it's a convenient place where we have automatic access to the complete
    // form array, but also to minimize the chance that the HTML we add will
    // get clobbered by code that runs after we have added it.
    '#after_build' => array_merge(element_info_property('submit', '#after_build', array()), array('views_ui_add_ajax_wrapper')),
  );
  // Copy #weight and #access from the triggering element to the button, so
  // that the two elements will be displayed together.
  foreach (array('#weight', '#access') as $property) {
    if (isset($triggering_element[$property])) {
      $wrapping_element[$button_key][$property] = $triggering_element[$property];
    }
  }
  // For easiest integration with the form API and the testing framework, we
  // always give the button a unique #value, rather than playing around with
  // #name.
  $button_title = !empty($triggering_element['#title']) ? $triggering_element['#title'] : $trigger_key;
  if (empty($seen_buttons[$button_title])) {
    $wrapping_element[$button_key]['#value'] = t('Update "@title" choice', array(
      '@title' => $button_title,
    ));
    $seen_buttons[$button_title] = 1;
  }
  else {
    $wrapping_element[$button_key]['#value'] = t('Update "@title" choice (@number)', array(
      '@title' => $button_title,
      '@number' => ++$seen_buttons[$button_title],
    ));
  }

  // Attach custom data to the triggering element and submit button, so we can
  // use it in both the process function and AJAX callback.
  $ajax_data = array(
    'wrapper' => $triggering_element['#ajax']['wrapper'],
    'trigger_key' => $trigger_key,
    'refresh_parents' => $refresh_parents,
    // Keep track of duplicate wrappers so we don't add the same wrapper to the
    // page more than once.
    'duplicate_wrapper' => !empty($seen_ids[$triggering_element['#ajax']['wrapper']]),
  );
  $seen_ids[$triggering_element['#ajax']['wrapper']] = TRUE;
  $triggering_element['#views_ui_ajax_data'] = $ajax_data;
  $wrapping_element[$button_key]['#views_ui_ajax_data'] = $ajax_data;
}

/**
 * Processes a non-JavaScript fallback submit button to limit its validation errors.
 */
function views_ui_add_limited_validation($element, &$form_state) {
  // Retrieve the AJAX triggering element so we can determine its parents. (We
  // know it's at the same level of the complete form array as the submit
  // button, so all we have to do to find it is swap out the submit button's
  // last array parent.)
  $array_parents = $element['#array_parents'];
  array_pop($array_parents);
  $array_parents[] = $element['#views_ui_ajax_data']['trigger_key'];
  $ajax_triggering_element = drupal_array_get_nested_value($form_state['complete_form'], $array_parents);

  // Limit this button's validation to the AJAX triggering element, so it can
  // update the form for that change without requiring that the rest of the
  // form be filled out properly yet.
  $element['#limit_validation_errors'] = array($ajax_triggering_element['#parents']);

  // If we are in the process of a form submission and this is the button that
  // was clicked, the form API workflow in form_builder() will have already
  // copied it to $form_state['triggering_element'] before our #process
  // function is run. So we need to make the same modifications in $form_state
  // as we did to the element itself, to ensure that #limit_validation_errors
  // will actually be set in the correct place.
  if (!empty($form_state['triggering_element'])) {
    $clicked_button = &$form_state['triggering_element'];
    if ($clicked_button['#name'] == $element['#name'] && $clicked_button['#value'] == $element['#value']) {
      $clicked_button['#limit_validation_errors'] = $element['#limit_validation_errors'];
    }
  }

  return $element;
}

/**
 * After-build function that adds a wrapper to a form region (for AJAX refreshes).
 *
 * This function inserts a wrapper around the region of the form that needs to
 * be refreshed by AJAX, based on information stored in the corresponding
 * submit button form element.
 */
function views_ui_add_ajax_wrapper($element, &$form_state) {
  // Don't add the wrapper <div> if the same one was already inserted on this
  // form.
  if (empty($element['#views_ui_ajax_data']['duplicate_wrapper'])) {
    // Find the region of the complete form that needs to be refreshed by AJAX.
    // This was earlier stored in a property on the element.
    $complete_form = &$form_state['complete_form'];
    $refresh_parents = $element['#views_ui_ajax_data']['refresh_parents'];
    $refresh_element = drupal_array_get_nested_value($complete_form, $refresh_parents);

    // The HTML ID that AJAX expects was also stored in a property on the
    // element, so use that information to insert the wrapper <div> here.
    $id = $element['#views_ui_ajax_data']['wrapper'];
    $refresh_element += array(
      '#prefix' => '',
      '#suffix' => '',
    );
    $refresh_element['#prefix'] = '<div id="' . $id . '" class="views-ui-ajax-wrapper">' . $refresh_element['#prefix'];
    $refresh_element['#suffix'] .= '</div>';

    // Copy the element that needs to be refreshed back into the form, with our
    // modifications to it.
    drupal_array_set_nested_value($complete_form, $refresh_parents, $refresh_element);
  }

  return $element;
}

/**
 * Updates a part of the add view form via AJAX.
 *
 * @return
 *   The part of the form that has changed.
 */
function views_ui_ajax_update_form($form, $form_state) {
  // The region that needs to be updated was stored in a property of the
  // triggering element by views_ui_add_ajax_trigger(), so all we have to do is
  // retrieve that here.
  return drupal_array_get_nested_value($form, $form_state['triggering_element']['#views_ui_ajax_data']['refresh_parents']);
}

/**
 * Non-Javascript fallback for updating the add view form.
 */
function views_ui_nojs_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
}

/**
 * Form element validation handler for a taxonomy autocomplete field.
 *
 * This allows a taxonomy autocomplete field to be validated outside the
 * standard Field API workflow, without passing in a complete field widget.
 * Instead, all that is required is that $element['#field_name'] contain the
 * name of the taxonomy autocomplete field that is being validated.
 *
 * This function is currently not used for validation directly, although it
 * could be. Instead, it is only used to store the term IDs and vocabulary name
 * in the element value, based on the tags that the user typed in.
 *
 * @see taxonomy_autocomplete_validate()
 */
function views_ui_taxonomy_autocomplete_validate($element, &$form_state) {
  $value = array();
  if ($tags = $element['#value']) {
    // Get the machine names of the vocabularies we will search, keyed by the
    // vocabulary IDs.
    $field = field_info_field($element['#field_name']);
    $vocabularies = array();
    if (!empty($field['settings']['allowed_values'])) {
      foreach ($field['settings']['allowed_values'] as $tree) {
        if ($vocabulary = taxonomy_vocabulary_machine_name_load($tree['vocabulary'])) {
          $vocabularies[$vocabulary->vid] = $tree['vocabulary'];
        }
      }
    }
    // Store the term ID of each (valid) tag that the user typed.
    $typed_terms = drupal_explode_tags($tags);
    foreach ($typed_terms as $typed_term) {
      if ($terms = entity_load_multiple_by_properties('taxonomy_term', array('name' => trim($typed_term), 'vid' => array_keys($vocabularies)))) {
        $term = array_pop($terms);
        $value['tids'][] = $term->tid;
      }
    }
    // Store the term IDs along with the name of the vocabulary. Currently
    // Views (as well as the Field UI) assumes that there will only be one
    // vocabulary, although technically the API allows there to be more than
    // one.
    if (!empty($value['tids'])) {
      $value['tids'] = array_unique($value['tids']);
      $value['vocabulary'] = array_pop($vocabularies);
    }
  }
  form_set_value($element, $value, $form_state);
}

/**
 * Page to delete a view.
 */
function views_ui_break_lock_confirm($form, &$form_state, ViewUI $view) {
  $form_state['view'] = &$view;
  $form = array();

  if (empty($view->locked)) {
    $form['message']['#markup'] = t('There is no lock on view %name to break.', array('%name' => $view->get('name')));
    return $form;
  }

  $cancel = drupal_container()->get('request')->query->get('cancel');
  if (empty($cancel)) {
    $cancel = 'admin/structure/views/view/' . $view->get('name') . '/edit';
  }

  $account = user_load($view->locked->owner);
  $form = confirm_form($form,
                  t('Do you want to break the lock on view %name?',
                  array('%name' => $view->get('name'))),
                  $cancel,
                  t('By breaking this lock, any unsaved changes made by !user will be lost.', array('!user' => theme('username', array('account' => $account)))),
                  t('Break lock'),
                  t('Cancel'));
  $form['actions']['submit']['#submit'][] = array($view, 'submitBreakLock');
  return $form;
}

/**
 * Page callback for the Edit View page.
 */
function views_ui_edit_page(ViewUI $view, $display_id = NULL) {
  $view->displayID = $display_id;
  $build['edit'] = entity_get_form($view, 'edit');
  $build['preview'] = entity_get_form($view, 'preview');
  return $build;
}

/**
 * Page callback for rendering the preview form and the preview of the view.
 *
 * @param \Drupal\views_ui\ViewUI $view
 *   The view UI object to preview.
 * @param string $display_id
 *   The display ID to preview.
 *
 * @return array
 *   The form array of the full preview form.
 */
function views_ui_build_preview(ViewUI $view, $display_id) {
  $view->displayID = $display_id;
  return entity_get_form($view, 'preview');
}

/**
 * Move form elements into details for presentation purposes.
 *
 * Many views forms use #tree = TRUE to keep their values in a hierarchy for
 * easier storage. Moving the form elements into fieldsets during form building
 * would break up that hierarchy. Therefore, we wait until the pre_render stage,
 * where any changes we make affect presentation only and aren't reflected in
 * $form_state['values'].
 */
function views_ui_pre_render_add_fieldset_markup($form) {
  foreach (element_children($form) as $key) {
    $element = $form[$key];
    // In our form builder functions, we added an arbitrary #fieldset property
    // to any element that belongs in a fieldset. If this form element has that
    // property, move it into its fieldset.
    if (isset($element['#fieldset']) && isset($form[$element['#fieldset']])) {
      $form[$element['#fieldset']][$key] = $element;
      // Remove the original element this duplicates.
      unset($form[$key]);
    }
  }

  return $form;
}

/**
 * Flattens the structure of an element containing the #flatten property.
 *
 * If a form element has #flatten = TRUE, then all of it's children
 * get moved to the same level as the element itself.
 * So $form['to_be_flattened'][$key] becomes $form[$key], and
 * $form['to_be_flattened'] gets unset.
 */
function views_ui_pre_render_flatten_data($form) {
  foreach (element_children($form) as $key) {
    $element = $form[$key];
    if (!empty($element['#flatten'])) {
      foreach (element_children($element) as $child_key) {
        $form[$child_key] = $form[$key][$child_key];
      }
      // All done, remove the now-empty parent.
      unset($form[$key]);
    }
  }

  return $form;
}

/**
 * Moves argument options into their place.
 *
 * When configuring the default argument behavior, almost each of the radio
 * buttons has its own fieldset shown bellow it when the radio button is
 * clicked. That fieldset is created through a custom form process callback.
 * Each element that has #argument_option defined and pointing to a default
 * behavior gets moved to the appropriate fieldset.
 * So if #argument_option is specified as 'default', the element is moved
 * to the 'default_options' fieldset.
 */
function views_ui_pre_render_move_argument_options($form) {
  foreach (element_children($form) as $key) {
    $element = $form[$key];
    if (!empty($element['#argument_option'])) {
      $container_name = $element['#argument_option'] . '_options';
      if (isset($form['no_argument']['default_action'][$container_name])) {
        $form['no_argument']['default_action'][$container_name][$key] = $element;
      }
      // Remove the original element this duplicates.
      unset($form[$key]);
    }
  }
  return $form;
}

/**
 * Add a <select> dropdown for a given section, allowing the user to
 * change whether this info is stored on the default display or on
 * the current display.
 */
function views_ui_standard_display_dropdown(&$form, &$form_state, $section) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $executable = $view->get('executable');
  $displays = $executable->displayHandlers;
  $current_display = $executable->display_handler;

  // Add the "2 of 3" progress indicator.
  // @TODO: Move this to a separate function if it's needed on any forms that
  // don't have the display dropdown.
  if ($form_progress = $view->getFormProgress()) {
    $form['progress']['#markup'] = '<div id="views-progress-indicator">' . t('@current of @total', array('@current' => $form_progress['current'], '@total' => $form_progress['total'])) . '</div>';
    $form['progress']['#weight'] = -1001;
  }

  if ($current_display->isDefaultDisplay()) {
    return;
  }

  // Determine whether any other displays have overrides for this section.
  $section_overrides = FALSE;
  $section_defaulted = $current_display->isDefaulted($section);
  foreach ($displays as $id => $display) {
    if ($id === 'default' || $id === $display_id) {
      continue;
    }
    if ($display && !$display->isDefaulted($section)) {
      $section_overrides = TRUE;
    }
  }

  $display_dropdown['default'] = ($section_overrides ? t('All displays (except overridden)') : t('All displays'));
  $display_dropdown[$display_id] = t('This @display_type (override)', array('@display_type' => $current_display->getPluginId()));
  // Only display the revert option if we are in a overridden section.
  if (!$section_defaulted) {
    $display_dropdown['default_revert'] = t('Revert to default');
  }

  $form['override'] = array(
    '#prefix' => '<div class="views-override clearfix container-inline">',
    '#suffix' => '</div>',
    '#weight' => -1000,
    '#tree' => TRUE,
  );
  $form['override']['dropdown'] = array(
    '#type' => 'select',
    '#title' => t('For'), // @TODO: Translators may need more context than this.
    '#options' => $display_dropdown,
  );
  if ($current_display->isDefaulted($section)) {
    $form['override']['dropdown']['#default_value'] = 'defaults';
  }
  else {
    $form['override']['dropdown']['#default_value'] = $display_id;
  }

}


/**
 * Returns information about subforms for editing the pieces of a view.
 *
 * @param string|null $key
 *   The form for which to retrieve data. If NULL, the list of all forms is
 *   returned.
 */
function views_ui_ajax_forms($key = NULL) {
  $forms = array(
    'display' => array(
      'form_id' => 'views_ui_edit_display_form',
      'args' => array('section'),
    ),
    'remove-display' => array(
      'form_id' => 'views_ui_remove_display_form',
      'args' => array(),
    ),
    'rearrange' => array(
      'form_id' => 'views_ui_rearrange_form',
      'args' => array('type'),
    ),
    'rearrange-filter' => array(
      'form_id' => 'views_ui_rearrange_filter_form',
      'args' => array('type'),
    ),
    'reorder-displays' => array(
      'form_id' => 'views_ui_reorder_displays_form',
      'args' => array(),
      'callback' => 'buildDisplaysReorderForm',
    ),
    'add-item' => array(
      'form_id' => 'views_ui_add_item_form',
      'args' => array('type'),
    ),
    'config-item' => array(
      'form_id' => 'views_ui_config_item_form',
      'args' => array('type', 'id'),
    ),
    'config-item-extra' => array(
      'form_id' => 'views_ui_config_item_extra_form',
      'args' => array('type', 'id'),
    ),
    'config-item-group' => array(
      'form_id' => 'views_ui_config_item_group_form',
      'args' => array('type', 'id'),
    ),
    'edit-details' => array(
      'form_id' => 'views_ui_edit_details_form',
      'args' => array(),
    ),
    'analyze' => array(
      'form_id' => 'views_ui_analyze_view_form',
      'args' => array(),
    ),
  );

  if ($key) {
    return !empty($forms[$key]) ? $forms[$key] : NULL;
  }

  return $forms;
}

/**
 * Create the URL for one of our standard AJAX forms based upon known
 * information about the form.
 */
function views_ui_build_form_url($form_state) {
  $form = views_ui_ajax_forms($form_state['form_key']);
  $ajax = empty($form_state['ajax']) ? 'nojs' : 'ajax';
  $name = $form_state['view']->get('name');
  $url = "admin/structure/views/$ajax/$form_state[form_key]/$name/$form_state[display_id]";
  foreach ($form['args'] as $arg) {
    $url .= '/' . $form_state[$arg];
  }
  return $url;
}

/**
 * Generic entry point to handle forms.
 *
 * We do this for consistency and to make it easy to chain forms
 * together.
 */
function views_ui_ajax_form($js, $key, ViewUI $view, $display_id = '') {
  // Reset the cache of IDs. Drupal rather aggressively prevents ID
  // duplication but this causes it to remember IDs that are no longer even
  // being used.
  $seen_ids_init = &drupal_static('drupal_html_id:init');
  $seen_ids_init = array();

  $form = views_ui_ajax_forms($key);
  if (empty($form)) {
    return MENU_NOT_FOUND;
  }

  views_include('ajax');
  $args = func_get_args();
  // Remove the known args
  array_splice($args, 0, 4);

  $form_state = $view->buildFormState($js, $key, $display_id, $args);
  // check to see if this is the top form of the stack. If it is, pop
  // it off; if it isn't, the user clicked somewhere else and the stack is
  // now irrelevant.
  if (!empty($view->stack)) {
    $identifier = $view->buildIdentifier($key, $display_id, $args);
    // Retrieve the first form from the stack without changing the integer keys,
    // as they're being used for the "2 of 3" progress indicator.
    reset($view->stack);
    list($key, $top) = each($view->stack);
    unset($view->stack[$key]);

    if (array_shift($top) != $identifier) {
      $view->stack = array();
    }
  }

  // Automatically remove the form cache if it is set and the key does
  // not match. This way navigating away from the form without hitting
  // update will work.
  if (isset($view->form_cache) && $view->form_cache['key'] != $key) {
    unset($view->form_cache);
  }

  // With the below logic, we may end up rendering a form twice (or two forms
  // each sharing the same element ids), potentially resulting in
  // drupal_add_js() being called twice to add the same setting. drupal_get_js()
  // is ok with that, but until ajax_render() is (http://drupal.org/node/208611),
  // reset the drupal_add_js() static before rendering the second time.
  $drupal_add_js_original = drupal_add_js();
  $drupal_add_js = &drupal_static('drupal_add_js');
  $output = views_ajax_form_wrapper($form_state['form_id'], $form_state);
  if ($form_state['submitted'] && empty($form_state['rerender'])) {
    // Sometimes we need to re-generate the form for multi-step type operations.
    $object = NULL;
    if (!empty($view->stack)) {
      $drupal_add_js = $drupal_add_js_original;
      $stack = $view->stack;
      $top = array_shift($stack);
      $top[0] = $js;
      $form_state = call_user_func_array(array($view, 'buildFormState'), $top);
      $form_state['input'] = array();
      $form_state['url'] = url(views_ui_build_form_url($form_state));
      if (!$js) {
        return drupal_goto(views_ui_build_form_url($form_state));
      }
      $output = views_ajax_form_wrapper($form_state['form_id'], $form_state);
    }
    elseif (!$js) {
      // if nothing on the stack, non-js forms just go back to the main view editor.
      return drupal_goto("admin/structure/views/view/{$view->get('name')}/edit");
    }
    else {
      $output = array();
      $output[] = views_ajax_command_dismiss_form();
      $output[] = views_ajax_command_show_buttons();
      $output[] = views_ajax_command_trigger_preview();
      if (!empty($form_state['#page_title'])) {
        $output[] = views_ajax_command_replace_title($form_state['#page_title']);
      }
    }
    // If this form was for view-wide changes, there's no need to regenerate
    // the display section of the form.
    if ($display_id !== '') {
      entity_form_controller('view', 'edit')->rebuildCurrentTab($view, $output, $display_id);
    }
  }

  return $js ? array('#type' => 'ajax', '#commands' => $output) : $output;
}

/**
 * Form constructor callback to display analysis information on a view
 */
function views_ui_analyze_view_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['#title'] = t('View analysis');
  $form['#section'] = 'analyze';

  $analyzer = new Analyzer($view->get('executable'));
  $messages = $analyzer->getMessages();

  $form['analysis'] = array(
    '#prefix' => '<div class="form-item">',
    '#suffix' => '</div>',
    '#markup' => $analyzer->formatMessages($messages),
  );

  // Inform the standard button function that we want an OK button.
  $form_state['ok_button'] = TRUE;
  $view->getStandardButtons($form, $form_state, 'views_ui_analyze_view_form');
  return $form;
}

/**
 * Submit handler for views_ui_analyze_view_form
 */
function views_ui_analyze_view_form_submit($form, &$form_state) {
  $form_state['redirect'] = 'admin/structure/views/view/' . $form_state['view']->get('name') . '/edit';
}

/**
 * Form builder to edit details of a view.
 */
function views_ui_edit_details_form($form, &$form_state) {
  $view = &$form_state['view'];

  $form['#title'] = t('View name and description');
  $form['#section'] = 'details';

  $form['details'] = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('class' => array('scroll')),
  );
  $form['details']['human_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Human-readable name'),
    '#description' => t('A descriptive human-readable name for this view. Spaces are allowed'),
    '#default_value' => $view->getHumanName(),
  );
  $form['details']['tag'] = array(
    '#type' => 'textfield',
    '#title' => t('View tag'),
    '#description' => t('Optionally, enter a comma delimited list of tags for this view to use in filtering and sorting views on the administrative page.'),
    '#default_value' => $view->get('tag'),
    '#autocomplete_path' => 'admin/views/ajax/autocomplete/tag',
  );
  $form['details']['description'] = array(
    '#type' => 'textfield',
    '#title' => t('View description'),
    '#description' => t('This description will appear on the Views administrative UI to tell you what the view is about.'),
    '#default_value' => $view->get('description'),
  );

  $view->getStandardButtons($form, $form_state, 'views_ui_edit_details_form');
  return $form;
}

/**
 * Submit handler for views_ui_edit_details_form.
 */
function views_ui_edit_details_form_submit($form, &$form_state) {
  $view = $form_state['view'];
  foreach ($form_state['values'] as $key => $value) {
    // Only save values onto the view if they're actual view properties
    // (as opposed to 'op' or 'form_build_id').
    if (isset($form['details'][$key])) {
      $view->set($key, $value);
    }
  }
  $form_state['#page_title'] = views_ui_edit_page_title($view);
  views_ui_cache_set($view);
}

/**
 * Form constructor callback to edit display of a view
 */
function views_ui_edit_display_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $section = $form_state['section'];

  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$executable->display[$display_id];

  // Get form from the handler.
  $form['options'] = array(
    '#theme_wrappers' => array('container'),
    '#attributes' => array('class' => array('scroll')),
  );
  $executable->display_handler->buildOptionsForm($form['options'], $form_state);

  // The handler options form sets $form['#title'], which we need on the entire
  // $form instead of just the ['options'] section.
  $form['#title'] = $form['options']['#title'];
  unset($form['options']['#title']);

  // Move the override dropdown out of the scrollable section of the form.
  if (isset($form['options']['override'])) {
    $form['override'] = $form['options']['override'];
    unset($form['options']['override']);
  }

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  $view->getStandardButtons($form, $form_state, 'views_ui_edit_display_form', $name);
  return $form;
}

/**
 * Validate handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_validate($form, &$form_state) {
  $form_state['view']->get('executable')->displayHandlers[$form_state['display_id']]->validateOptionsForm($form['options'], $form_state);

  if (form_get_errors()) {
    $form_state['rerender'] = TRUE;
  }
}

/**
 * Submit handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_submit($form, &$form_state) {
  $form_state['view']->get('executable')->displayHandlers[$form_state['display_id']]->submitOptionsForm($form['options'], $form_state);

  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler for views_ui_edit_display_form
 *
 * @TODO: Not currently used. Remove unless we implement an override toggle.
 */
function views_ui_edit_display_form_override($form, &$form_state) {
  $form_state['view']->get('executable')->displayHandlers[$form_state['display_id']]->optionsOverride($form['options'], $form_state);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Form to rearrange items in the views UI.
 */
function views_ui_rearrange_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = ViewExecutable::viewsHandlerTypes();
  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$executable->displayHandlers[$display_id];
  $form['#title'] = t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'rearrange-item';

  if ($display->defaultableSections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
  }

  $count = 0;

  // Get relationship labels
  $relationships = array();
  foreach ($display->getHandlers('relationship') as $id => $handler) {
    $relationships[$id] = $handler->label();
  }

  // Filters can now be grouped so we do a little bit extra:
  $groups = array();
  $grouping = FALSE;
  if ($type == 'filter') {
    $group_info = $executable->display_handler->getOption('filter_groups');
    if (!empty($group_info['groups']) && count($group_info['groups']) > 1) {
      $grouping = TRUE;
      $groups = array(0 => array());
    }
  }

  foreach ($display->getOption($types[$type]['plural']) as $id => $field) {
    $form['fields'][$id] = array('#tree' => TRUE);
    $form['fields'][$id]['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$count,
    );
    $handler = $display->getHandler($type, $id);
    if ($handler) {
      $name = $handler->adminLabel() . ' ' . $handler->adminSummary();
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
      }

      $form['fields'][$id]['name'] = array(
        '#markup' => $name,
      );
    }
    else {
      $form['fields'][$id]['name'] = array('#markup' => t('Broken field @id', array('@id' => $id)));
    }
    $form['fields'][$id]['removed'] = array(
      '#type' => 'checkbox',
      '#id' => 'views-removed-' . $id,
      '#attributes' => array('class' => array('views-remove-checkbox')),
      '#default_value' => 0,
    );
  }

  // Add javascript settings that will be added via $.extend for tabledragging
  $form['#js']['tableDrag']['arrange']['weight'][0] = array(
    'target' => 'weight',
    'source' => NULL,
    'relationship' => 'sibling',
    'action' => 'order',
    'hidden' => TRUE,
    'limit' => 0,
  );

  $name = NULL;
  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  $view->getStandardButtons($form, $form_state, 'views_ui_rearrange_form');
  return $form;
}

/**
 * Submit handler for rearranging form.
 */
function views_ui_rearrange_form_submit($form, &$form_state) {
  $types = ViewExecutable::viewsHandlerTypes();
  $display = &$form_state['view']->get('executable')->displayHandlers[$form_state['display_id']];

  $old_fields = $display->getOption($types[$form_state['type']]['plural']);
  $new_fields = $order = array();

  // Make an array with the weights
  foreach ($form_state['values'] as $field => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && isset($info['weight']) && empty($info['removed'])) {
      $order[$field] = $info['weight'];
    }
  }

  // Sort the array
  asort($order);

  // Create a new list of fields in the new order.
  foreach (array_keys($order) as $field) {
    $new_fields[$field] = $old_fields[$field];
  }
  $display->setOption($types[$form_state['type']]['plural'], $new_fields);

  // Store in cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to rearrange items in the views UI.
 */
function views_ui_rearrange_filter_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $types = ViewExecutable::viewsHandlerTypes();
  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_render(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = $executable->displayHandlers[$display_id];
  $form['#title'] = check_plain($display->display['display_title']) . ': ';
  $form['#title'] .= t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
  $form['#section'] = $display_id . 'rearrange-item';

  if ($display->defaultableSections($types[$type]['plural'])) {
    $form_state['section'] = $types[$type]['plural'];
    views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
  }

  if (!empty($view->form_cache)) {
    $groups = $view->form_cache['groups'];
    $handlers = $view->form_cache['handlers'];
  }
  else {
    $groups = $display->getOption('filter_groups');
    $handlers = $display->getOption($types[$type]['plural']);
  }
  $count = 0;

  // Get relationship labels
  $relationships = array();
  foreach ($display->getHandlers('relationship') as $id => $handler) {
    $relationships[$id] = $handler->label();
  }

  $group_options = array();

  /**
   * Filter groups is an array that contains:
   * array(
   *   'operator' => 'and' || 'or',
   *   'groups' => array(
   *     $group_id => 'and' || 'or',
   *   ),
   * );
   */

  $grouping = count(array_keys($groups['groups'])) > 1;

  $form['filter_groups']['#tree'] = TRUE;
  $form['filter_groups']['operator'] = array(
    '#type' => 'select',
    '#options' => array(
      'AND' => t('And'),
      'OR' => t('Or'),
    ),
    '#default_value' => $groups['operator'],
    '#attributes' => array(
      'class' => array('warning-on-change'),
    ),
    '#title' => t('Operator to use on all groups'),
    '#description' => t('Either "group 0 AND group 1 AND group 2" or "group 0 OR group 1 OR group 2", etc'),
    '#access' => $grouping,
  );

  $form['remove_groups']['#tree'] = TRUE;

  foreach ($groups['groups'] as $id => $group) {
    $form['filter_groups']['groups'][$id] = array(
      '#title' => t('Operator'),
      '#type' => 'select',
      '#options' => array(
        'AND' => t('And'),
        'OR' => t('Or'),
      ),
      '#default_value' => $group,
      '#attributes' => array(
        'class' => array('warning-on-change'),
      ),
    );

    $form['remove_groups'][$id] = array(); // to prevent a notice
    if ($id != 1) {
      $form['remove_groups'][$id] = array(
        '#type' => 'submit',
        '#value' => t('Remove group @group', array('@group' => $id)),
        '#id' => "views-remove-group-$id",
        '#attributes' => array(
          'class' => array('views-remove-group'),
        ),
        '#group' => $id,
      );
    }
    $group_options[$id] = $id == 1 ? t('Default group') : t('Group @group', array('@group' => $id));
    $form['#group_renders'][$id] = array();
  }

  $form['#group_options'] = $group_options;
  $form['#groups'] = $groups;
  // We don't use getHandlers() because we want items without handlers to
  // appear and show up as 'broken' so that the user can see them.
  $form['filters'] = array('#tree' => TRUE);
  foreach ($handlers as $id => $field) {
    // If the group does not exist, move the filters to the default group.
    if (empty($field['group']) || empty($groups['groups'][$field['group']])) {
      $field['group'] = 1;
    }

    $handler = $display->getHandler($type, $id);
    if ($grouping && $handler && !$handler->can_group()) {
      $field['group'] = 'ungroupable';
    }

    // If not grouping and the handler is set ungroupable, move it back to
    // the default group to prevent weird errors from having it be in its
    // own group:
    if (!$grouping && $field['group'] == 'ungroupable') {
      $field['group'] = 1;
    }

    // Place this item into the proper group for rendering.
    $form['#group_renders'][$field['group']][] = $id;

    $form['filters'][$id]['weight'] = array(
      '#type' => 'textfield',
      '#default_value' => ++$count,
      '#size' => 8,
    );
    $form['filters'][$id]['group'] = array(
      '#type' => 'select',
      '#options' => $group_options,
      '#default_value' => $field['group'],
      '#attributes' => array(
        'class' => array('views-region-select', 'views-region-' . $id),
      ),
      '#access' => $field['group'] !== 'ungroupable',
    );

    if ($handler) {
      $name = $handler->adminLabel() . ' ' . $handler->adminSummary();
      if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
        $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
      }

      $form['filters'][$id]['name'] = array(
        '#markup' => $name,
      );
    }
    else {
      $form['filters'][$id]['name'] = array('#markup' => t('Broken field @id', array('@id' => $id)));
    }
    $form['filters'][$id]['removed'] = array(
      '#type' => 'checkbox',
      '#id' => 'views-removed-' . $id,
      '#attributes' => array('class' => array('views-remove-checkbox')),
      '#default_value' => 0,
    );
  }

  if (isset($form_state['update_name'])) {
    $name = $form_state['update_name'];
  }

  $view->getStandardButtons($form, $form_state, 'views_ui_rearrange_filter_form');
  $form['buttons']['add_group'] = array(
    '#type' => 'submit',
    '#value' => t('Create new filter group'),
    '#id' => 'views-add-group',
    '#group' => 'add',
  );

  return $form;
}

/**
 * Submit handler for rearranging form
 */
function views_ui_rearrange_filter_form_submit($form, &$form_state) {
  $types = ViewExecutable::viewsHandlerTypes();
  $display = &$form_state['view']->get('executable')->displayHandlers[$form_state['display_id']];
  $remember_groups = array();

  if (!empty($form_state['view']->form_cache)) {
    $old_fields = $form_state['view']->form_cache['handlers'];
  }
  else {
    $old_fields = $display->getOption($types[$form_state['type']]['plural']);
  }
  $count = 0;

  $groups = $form_state['values']['filter_groups'];
  // Whatever button was clicked, re-calculate field information.
  $new_fields = $order = array();

  // Make an array with the weights
  foreach ($form_state['values']['filters'] as $field => $info) {
    // add each value that is a field with a weight to our list, but only if
    // it has had its 'removed' checkbox checked.
    if (is_array($info) && empty($info['removed'])) {
      if (isset($info['weight'])) {
        $order[$field] = $info['weight'];
      }

      if (isset($info['group'])) {
        $old_fields[$field]['group'] = $info['group'];
        $remember_groups[$info['group']][] = $field;
      }
    }
  }

  // Sort the array
  asort($order);

  // Create a new list of fields in the new order.
  foreach (array_keys($order) as $field) {
    $new_fields[$field] = $old_fields[$field];
  }

  // If the #group property is set on the clicked button, that means we are
  // either adding or removing a group, not actually updating the filters.
  if (!empty($form_state['clicked_button']['#group'])) {
    if ($form_state['clicked_button']['#group'] == 'add') {
      // Add a new group
      $groups['groups'][] = 'AND';
    }
    else {
      // Renumber groups above the removed one down.
      foreach (array_keys($groups['groups']) as $group_id) {
        if ($group_id >= $form_state['clicked_button']['#group']) {
          $old_group = $group_id + 1;
          if (isset($groups['groups'][$old_group])) {
            $groups['groups'][$group_id] = $groups['groups'][$old_group];
            if (isset($remember_groups[$old_group])) {
              foreach ($remember_groups[$old_group] as $id) {
                $new_fields[$id]['group'] = $group_id;
              }
            }
          }
          else {
            // If this is the last one, just unset it.
            unset($groups['groups'][$group_id]);
          }
        }
      }
    }
    // Update our cache with values so that cancel still works the way
    // people expect.
    $form_state['view']->form_cache = array(
      'key' => 'rearrange-filter',
      'groups' => $groups,
      'handlers' => $new_fields,
    );

    // Return to this form except on actual Update.
    $form_state['view']->addFormToStack('rearrange-filter', $form_state['display_id'], array($form_state['type']));
  }
  else {
    // The actual update button was clicked. Remove the empty groups, and
    // renumber them sequentially.
    ksort($remember_groups);
    $groups['groups'] = views_array_key_plus(array_values(array_intersect_key($groups['groups'], $remember_groups)));
    // Change the 'group' key on each field to match. Here, $mapping is an
    // array whose keys are the old group numbers and whose values are the new
    // (sequentially numbered) ones.
    $mapping = array_flip(views_array_key_plus(array_keys($remember_groups)));
    foreach ($new_fields as &$new_field) {
      $new_field['group'] = $mapping[$new_field['group']];
    }

    // Write the changed handler values.
    $display->setOption($types[$form_state['type']]['plural'], $new_fields);
    $display->setOption('filter_groups', $groups);
    if (isset($form_state['view']->form_cache)) {
      unset($form_state['view']->form_cache);
    }
  }

  // Store in cache.
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to add_item items in the views UI.
 */
function views_ui_add_item_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];

  $form = array(
    'options' => array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );

  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $display = &$executable->displayHandlers[$display_id];

  $types = ViewExecutable::viewsHandlerTypes();
  $ltitle = $types[$type]['ltitle'];
  $section = $types[$type]['plural'];

  if (!empty($types[$type]['type'])) {
    $type = $types[$type]['type'];
  }

  $form['#title'] = t('Add @type', array('@type' => $ltitle));
  $form['#section'] = $display_id . 'add-item';

  // Add the display override dropdown.
  views_ui_standard_display_dropdown($form, $form_state, $section);

  // Figure out all the base tables allowed based upon what the relationships provide.
  $base_tables = $executable->getBaseTables();
  $options = views_fetch_fields(array_keys($base_tables), $type, $display->useGroupBy(), $form_state['type']);

  if (!empty($options)) {
    $form['options']['controls'] = array(
      '#theme_wrappers' => array('container'),
      '#id' => 'views-filterable-options-controls',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['options']['controls']['options_search'] = array(
      '#type' => 'textfield',
      '#title' => t('Search'),
    );

    $groups = array('all' => t('- All -'));
    $form['options']['controls']['group'] = array(
      '#type' => 'select',
      '#title' => t('Filter'),
      '#options' => array(),
    );

    $form['options']['name'] = array(
      '#prefix' => '<div class="views-radio-box form-checkboxes views-filterable-options">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#default_value' => 'all',
    );

    // Group options first to simplify the usage of #states.
    $grouped_options = array();
    foreach ($options as $key => $option) {
      $group = preg_replace('/[^a-z0-9]/', '-', strtolower($option['group']));
      $groups[$group] = $option['group'];
      $grouped_options[$group][$key] = $option;
      if (!empty($option['aliases']) && is_array($option['aliases'])) {
        foreach ($option['aliases'] as $id => $alias) {
          if (empty($alias['base']) || !empty($base_tables[$alias['base']])) {
            $copy = $option;
            $copy['group'] = $alias['group'];
            $copy['title'] = $alias['title'];
            if (isset($alias['help'])) {
              $copy['help'] = $alias['help'];
            }

            $group = preg_replace('/[^a-z0-9]/', '-', strtolower($copy['group']));
            $groups[$group] = $copy['group'];
            $grouped_options[$group][$key . '$' . $id] = $copy;
          }
        }
      }
    }

    foreach ($grouped_options as $group => $group_options) {
      $zebra = 0;
      foreach ($group_options as $key => $option) {
        $zebra_class = ($zebra % 2) ? 'odd' : 'even';
        $form['options']['name'][$key] = array(
          '#type' => 'checkbox',
          '#title' => t('!group: !field', array('!group' => $option['group'], '!field' => $option['title'])),
          '#description' => $option['help'],
          '#return_value' => $key,
          '#prefix' => "<div class='$zebra_class filterable-option'>",
          '#suffix' => '</div>',
          '#states' => array(
            'visible' => array(
              array(
                ':input[name="group"]' => array('value' => 'all'),
              ),
              array(
                ':input[name="group"]' => array('value' => $group),
              ),
            )
          )
        );
        $zebra++;
      }
    }

    $form['options']['controls']['group']['#options'] = $groups;
  }
  else {
    $form['options']['markup'] = array(
      '#markup' => '<div class="form-item">' . t('There are no @types available to add.', array('@types' =>  $ltitle)) . '</div>',
    );
  }
  // Add a div to show the selected items
  $form['selected'] = array(
    '#type' => 'item',
    '#markup' => '<div class="views-selected-options"></div>',
    '#title' => t('Selected') . ':',
    '#theme_wrappers' => array('form_element', 'views_ui_container'),
    '#attributes' => array('class' => array('container-inline', 'views-add-form-selected')),
  );
  $view->getStandardButtons($form, $form_state, 'views_ui_add_item_form', t('Add and configure @types', array('@types' => $ltitle)));

  // Remove the default submit function.
  $form['buttons']['submit']['#submit'] = array_diff($form['buttons']['submit']['#submit'], array(array($view, 'standardSubmit')));
  $form['buttons']['submit']['#submit'][] = array($view, 'submitItemAdd');

  return $form;
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_config_item_form_build_group($form, &$form_state) {
  $item = &$form_state['handler']->options;
  // flip. If the filter was a group, set back to a standard filter.
  $item['is_grouped'] = empty($item['is_grouped']);

  // If necessary, set new defaults:
  if ($item['is_grouped']) {
    $form_state['handler']->build_group_options();
  }

  $form_state['view']->get('executable')->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  $form_state['view']->addFormToStack($form_state['form_key'], $form_state['display_id'], array($form_state['type'], $form_state['id']), TRUE, TRUE);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  $form_state['force_build_group_options'] = TRUE;
}

/**
 * Add a new group to the exposed filter groups.
 */
function views_ui_config_item_form_add_group($form, &$form_state) {
  $item =& $form_state['handler']->options;

  // Add a new row.
  $item['group_info']['group_items'][] = array();

  $form_state['view']->get('executable')->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  $form_state['force_build_group_options'] = TRUE;
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $executable->getItem($display_id, $type, $id);

  if ($item) {
    $handler = $executable->display_handler->getHandler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $types = ViewExecutable::viewsHandlerTypes();

      // If this item can come from the default display, show a dropdown
      // that lets the user choose which display the changes should apply to.
      if ($executable->display_handler->defaultableSections($types[$type]['plural'])) {
        $form_state['section'] = $types[$type]['plural'];
        views_ui_standard_display_dropdown($form, $form_state, $form_state['section']);
      }

      // A whole bunch of code to figure out what relationships are valid for
      // this item.
      $relationships = $executable->display_handler->getOption('relationships');
      $relationship_options = array();

      foreach ($relationships as $relationship) {
        // relationships can't link back to self. But also, due to ordering,
        // relationships can only link to prior relationships.
        if ($type == 'relationship' && $id == $relationship['id']) {
          break;
        }
        $relationship_handler = views_get_handler($relationship['table'], $relationship['field'], 'relationship');
        // ignore invalid/broken relationships.
        if (empty($relationship_handler)) {
          continue;
        }

        // If this relationship is valid for this type, add it to the list.
        $data = views_fetch_data($relationship['table']);
        $base = $data[$relationship['field']]['relationship']['base'];
        $base_fields = views_fetch_fields($base, $form_state['type'], $executable->display_handler->useGroupBy());
        if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
          $relationship_handler->init($executable, $relationship);
          $relationship_options[$relationship['id']] = $relationship_handler->label();
        }
      }

      if (!empty($relationship_options)) {
        // Make sure the existing relationship is even valid. If not, force
        // it to none.
        $base_fields = views_fetch_fields($view->get('base_table'), $form_state['type'], $executable->display_handler->useGroupBy());
        if (isset($base_fields[$item['table'] . '.' . $item['field']])) {
          $relationship_options = array_merge(array('none' => t('Do not use a relationship')), $relationship_options);
        }
        $rel = empty($item['relationship']) ? 'none' : $item['relationship'];
        if (empty($relationship_options[$rel])) {
          // Pick the first relationship.
          $rel = key($relationship_options);
          // We want this relationship option to get saved even if the user
          // skips submitting the form.
          $executable->setItemOption($display_id, $type, $id, 'relationship', $rel);
          $temp_view = $view->cloneView();
          views_ui_cache_set($temp_view);
        }

        $form['options']['relationship'] = array(
          '#type' => 'select',
          '#title' => t('Relationship'),
          '#options' => $relationship_options,
          '#default_value' => $rel,
          '#weight' => -500,
        );
      }
      else {
        $form['options']['relationship'] = array(
          '#type' => 'value',
          '#value' => 'none',
        );
      }

      $form['#title'] = t('Configure @type: @item', array('@type' => $types[$type]['lstitle'], '@item' => $handler->adminLabel()));

      if (!empty($handler->definition['help'])) {
        $form['options']['form_description'] = array(
          '#markup' => $handler->definition['help'],
          '#theme_wrappers' => array('container'),
          '#attributes' => array('class' => array('form-item description')),
          '#weight' => -1000,
        );
      }

      $form['#section'] = $display_id . '-' . $type . '-' . $id;

      // Get form from the handler.
      $handler->buildOptionsForm($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    $name = NULL;
    if (isset($form_state['update_name'])) {
      $name = $form_state['update_name'];
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_config_item_form', $name, t('Remove'), 'remove');
    // Only validate the override values, because this values are required for
    // the override selection.
    $form['buttons']['remove']['#limit_validation_errors'] = array(array('override'));
  }

  return $form;
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_form_validate($form, &$form_state) {
  $form_state['handler']->validateOptionsForm($form['options'], $form_state);

  if (form_get_errors()) {
    $form_state['rerender'] = TRUE;
  }
}

/**
 * A submit handler that is used for storing temporary items when using
 * multi-step changes, such as ajax requests.
 */
function views_ui_config_item_form_submit_temporary($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->submitOptionsForm($form['options'], $form_state);
  $item = $form_state['handler']->options;
  $types = ViewExecutable::viewsHandlerTypes();

  // For footer/header $handler_type is area but $type is footer/header.
  // For all other handle types it's the same.
  $handler_type = $type = $form_state['type'];
  if (!empty($types[$type]['type'])) {
    $handler_type = $types[$type]['type'];
  }

  $override = NULL;
  $executable = $form_state['view']->get('executable');
  if ($executable->display_handler->useGroupBy() && !empty($item['group_type'])) {
    if (empty($executable->query)) {
      $executable->initQuery();
    }
    $aggregate = $executable->query->get_aggregation_info();
    if (!empty($aggregate[$item['group_type']]['handler'][$type])) {
      $override = $aggregate[$item['group_type']]['handler'][$type];
    }
  }

  // Create a new handler and unpack the options from the form onto it. We
  // can use that for storage.
  $handler = views_get_handler($item['table'], $item['field'], $handler_type, $override);
  $handler->init($executable, $item);

  // Add the incoming options to existing options because items using
  // the extra form may not have everything in the form here.
  $options = $form_state['values']['options'] + $form_state['handler']->options;

  // This unpacks only options that are in the definition, ensuring random
  // extra stuff on the form is not sent through.
  $handler->unpackOptions($handler->options, $options, NULL, FALSE);

  // Store the item back on the view
  $form_state['view']->temporary_options[$type][$form_state['id']] = $handler->options;

  // @todo: Figure out whether views_ui_ajax_form is perhaps the better place to fix the issue.
  // views_ui_ajax_form() drops the current form from the stack, even if it's an #ajax.
  // So add the item back to the top of the stack.
  $form_state['view']->addFormToStack($form_state['form_key'], $form_state['display_id'], array($type, $item['id']), TRUE);

  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->submitOptionsForm($form['options'], $form_state);
  $item = $form_state['handler']->options;
  $types = ViewExecutable::viewsHandlerTypes();

  // For footer/header $handler_type is area but $type is footer/header.
  // For all other handle types it's the same.
  $handler_type = $type = $form_state['type'];
  if (!empty($types[$type]['type'])) {
    $handler_type = $types[$type]['type'];
  }

  $override = NULL;
  $executable = $form_state['view']->get('executable');
  if ($executable->display_handler->useGroupBy() && !empty($item['group_type'])) {
    if (empty($executable->query)) {
      $executable->initQuery();
    }
    $aggregate = $executable->query->get_aggregation_info();
    if (!empty($aggregate[$item['group_type']]['handler'][$type])) {
      $override = $aggregate[$item['group_type']]['handler'][$type];
    }
  }

  // Create a new handler and unpack the options from the form onto it. We
  // can use that for storage.
  $handler = views_get_handler($item['table'], $item['field'], $handler_type, $override);
  $handler->init($executable, $item);

  // Add the incoming options to existing options because items using
  // the extra form may not have everything in the form here.
  $options = $form_state['values']['options'] + $form_state['handler']->options;

  // This unpacks only options that are in the definition, ensuring random
  // extra stuff on the form is not sent through.
  $handler->unpackOptions($handler->options, $options, NULL, FALSE);

  // Store the item back on the view
  $executable->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], $handler->options);

  // Ensure any temporary options are removed.
  if (isset($form_state['view']->temporary_options[$type][$form_state['id']])) {
    unset($form_state['view']->temporary_options[$type][$form_state['id']]);
  }

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_group_form($type, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_render(t('Invalid display id @display', array('@display' => $display_id)));
  }

  $executable->initQuery();

  $item = $executable->getItem($display_id, $type, $id);

  if ($item) {
    $handler = $executable->display_handler->getHandler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = ViewExecutable::viewsHandlerTypes();

      $form['#title'] = t('Configure group settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->adminLabel()));

      $handler->buildGroupByForm($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_config_item_group_form');
  }
  return $form;
}

/**
 * Submit handler for configing group settings on a view.
 */
function views_ui_config_item_group_form_submit($form, &$form_state) {
  $item =& $form_state['handler']->options;
  $type = $form_state['type'];
  $id = $form_state['id'];

  $handler = views_get_handler($item['table'], $item['field'], $type);
  $handler->init($form_state['view']->get('executable'), $item);

  $handler->submitGroupByForm($form, $form_state);

  // Store the item back on the view
  $form_state['view']->get('executable')->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Submit handler for removing an item from a view
 */
function views_ui_config_item_form_remove($form, &$form_state) {
  // Store the item back on the view
  list($was_defaulted, $is_defaulted) = $form_state['view']->getOverrideValues($form, $form_state);
  // If the display selection was changed toggle the override value.
  if ($was_defaulted != $is_defaulted) {
    $display =& $form_state['view']->get('executable')->displayHandlers[$form_state['display_id']];
    $display->optionsOverride($form, $form_state);
  }
  $form_state['view']->get('executable')->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], NULL);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_config_item_form_expose($form, &$form_state) {
  $item = &$form_state['handler']->options;
  // flip
  $item['exposed'] = empty($item['exposed']);

  // If necessary, set new defaults:
  if ($item['exposed']) {
    $form_state['handler']->defaultExposeOptions();
  }

  $form_state['view']->get('executable')->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  $form_state['view']->addFormToStack($form_state['form_key'], $form_state['display_id'], array($form_state['type'], $form_state['id']), TRUE, TRUE);

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
  $form_state['force_expose_options'] = TRUE;
}

/**
 * Form to config_item items in the views UI.
 */
function views_ui_config_item_extra_form($form, &$form_state) {
  $view = &$form_state['view'];
  $display_id = $form_state['display_id'];
  $type = $form_state['type'];
  $id = $form_state['id'];

  $form = array(
    'options' => array(
      '#tree' => TRUE,
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll')),
    ),
  );
  $executable = $view->get('executable');
  if (!$executable->setDisplay($display_id)) {
    views_ajax_error(t('Invalid display id @display', array('@display' => $display_id)));
  }
  $item = $executable->getItem($display_id, $type, $id);

  if ($item) {
    $handler = $executable->display_handler->getHandler($type, $id);
    if (empty($handler)) {
      $form['markup'] = array('#markup' => t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
    }
    else {
      $handler->init($view, $item);
      $types = ViewExecutable::viewsHandlerTypes();

      $form['#title'] = t('Configure extra settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->adminLabel()));

      $form['#section'] = $display_id . '-' . $type . '-' . $id;

      // Get form from the handler.
      $handler->buildExtraOptionsForm($form['options'], $form_state);
      $form_state['handler'] = &$handler;
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_config_item_extra_form');
  }
  return $form;
}

/**
 * Validation handler for configing new item(s) to a view.
 */
function views_ui_config_item_extra_form_validate($form, &$form_state) {
  $form_state['handler']->validateExtraOptionsForm($form['options'], $form_state);
}

/**
 * Submit handler for configing new item(s) to a view.
 */
function views_ui_config_item_extra_form_submit($form, &$form_state) {
  // Run it through the handler's submit function.
  $form_state['handler']->submitExtraOptionsForm($form['options'], $form_state);
  $item = $form_state['handler']->options;

  // Store the data we're given.
  foreach ($form_state['values']['options'] as $key => $value) {
    $item[$key] = $value;
  }

  // Store the item back on the view
  $form_state['view']->get('executable')->setItem($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

  // Write to cache
  views_ui_cache_set($form_state['view']);
}

/**
 * Form builder for the admin display defaults page.
 */
function views_ui_admin_settings_basic($form, &$form_state) {
  $form = array();
  $form['#attached']['css'] = ViewFormControllerBase::getAdminCSS();

  $config = config('views.settings');

  $options = array();
  foreach (list_themes() as $name => $theme) {
    if ($theme->status) {
      $options[$name] = $theme->info['name'];
    }
  }

  // This is not currently a fieldset but we may want it to be later,
  // so this will make it easier to change if we do.
  $form['basic'] = array();

  $form['basic']['ui_show_listing_filters'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show filters on the list of views'),
    '#default_value' => $config->get('ui.show.listing_filters'),
  );

  $form['basic']['ui_show_master_display'] = array(
    '#type' => 'checkbox',
    '#title' => t('Always show the master display'),
    '#description' => t('Advanced users of views may choose to see the master (i.e. default) display.'),
    '#default_value' => $config->get('ui.show.master_display'),
  );

  $form['basic']['ui_show_advanced_column'] = array(
    '#type' => 'checkbox',
    '#title' => t('Always show advanced display settings'),
    '#description' => t('Default to showing advanced display settings, such as relationships and contextual filters.'),
    '#default_value' => $config->get('ui.show.advanced_column'),
  );

  $form['basic']['ui_show_display_embed'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show the embed display in the ui.'),
    '#description' => t('Allow advanced user to use the embed view display. The plugin itself works if it\'s not visible in the ui'),
    '#default_value' => $config->get('ui.show.display_embed'),
  );

  $form['basic']['ui_exposed_filter_any_label'] = array(
    '#type' => 'select',
    '#title' => t('Label for "Any" value on non-required single-select exposed filters'),
    '#options' => array('old_any' => '<Any>', 'new_any' => t('- Any -')),
    '#default_value' => $config->get('ui.exposed_filter_any_label'),
  );

  $form['live_preview'] = array(
    '#type' => 'details',
    '#title' => t('Live preview settings'),
  );

  $form['live_preview']['ui_always_live_preview'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically update preview on changes'),
    '#default_value' => $config->get('ui.always_live_preview'),
  );

  $form['live_preview']['ui_show_preview_information'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show information and statistics about the view during live preview'),
    '#default_value' => $config->get('ui.show.preview_information'),
  );

  $form['live_preview']['options'] = array(
    '#type' => 'container',
    '#states' => array(
      'visible' => array(
        ':input[name="ui_show_preview_information"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['live_preview']['options']['ui_show_sql_query_where'] = array(
    '#type' => 'radios',
    '#options' => array(
      'above' => t('Above the preview'),
      'below' => t('Below the preview'),
    ),
    '#default_value' => $config->get('ui.show.sql_query.where'),
  );

  $form['live_preview']['options']['ui_show_sql_query_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show the SQL query'),
    '#default_value' => $config->get('ui.show.sql_query.enabled'),
  );
  $form['live_preview']['options']['ui_show_performance_statistics'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show performance statistics'),
    '#default_value' => $config->get('ui.show.performance_statistics'),
  );

  $form['live_preview']['options']['ui_show_additional_queries'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show other queries run during render during live preview'),
    '#description' => t("Drupal has the potential to run many queries while a view is being rendered. Checking this box will display every query run during view render as part of the live preview."),
    '#default_value' => $config->get('ui.show.additional_queries'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Form builder submit handler; Handle submission the basic views settings.
 * @ingroup forms
 * @see system_settings_form()
 */
function views_ui_admin_settings_basic_submit(&$form, &$form_state) {
  config('views.settings')
    ->set('ui.show.listing_filters', $form_state['values']['ui_show_listing_filters'])
    ->set('ui.show.master_display', $form_state['values']['ui_show_master_display'])
    ->set('ui.show.advanced_column', $form_state['values']['ui_show_advanced_column'])
    ->set('ui.show.display_embed', $form_state['values']['ui_show_display_embed'])
    ->set('ui.exposed_filter_any_label', $form_state['values']['ui_exposed_filter_any_label'])
    ->set('ui.always_live_preview', $form_state['values']['ui_always_live_preview'])
    ->set('ui.show.preview_information', $form_state['values']['ui_show_preview_information'])
    ->set('ui.show.sql_query.where', $form_state['values']['ui_show_sql_query_where'])
    ->set('ui.show.sql_query.enabled', $form_state['values']['ui_show_sql_query_enabled'])
    ->set('ui.show.performance_statistics', $form_state['values']['ui_show_performance_statistics'])
    ->set('ui.show.additional_queries', $form_state['values']['ui_show_additional_queries'])
    ->save();
}

/**
 * Form builder for the advanced admin settings page.
 */
function views_ui_admin_settings_advanced() {
  $form = array();
  $form['#attached']['css'] = ViewFormControllerBase::getAdminCSS();

  $config = config('views.settings');

  $form['cache'] = array(
    '#type' => 'details',
    '#title' => t('Caching'),
  );

  $form['cache']['skip_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable views data caching'),
    '#description' => t("Views caches data about tables, modules and views available, to increase performance. By checking this box, Views will skip this cache and always rebuild this data when needed. This can have a serious performance impact on your site."),
    '#default_value' => $config->get('skip_cache'),
  );

  $form['cache']['clear_cache'] = array(
    '#type' => 'submit',
    '#value' => t("Clear Views' cache"),
    '#submit' => array('views_ui_tools_clear_cache'),
  );

  $form['debug'] = array(
    '#type' => 'details',
    '#title' => t('Debugging'),
  );

  $form['debug']['sql_signature'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add Views signature to all SQL queries'),
    '#description' => t("All Views-generated queries will include the name of the views and display 'view-name:display-name' as a string  at the end of the SELECT clause. This makes identifying Views queries in database server logs simpler, but should only be used when troubleshooting."),

    '#default_value' => $config->get('sql_signature'),
  );

  $form['debug']['no_javascript'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable JavaScript with Views'),
    '#description' => t("If you are having problems with the JavaScript, you can disable it here. The Views UI should degrade and still be usable without javascript; it's just not as good."),
    '#default_value' => $config->get('no_javascript'),
  );

  $form['debug']['debug_output'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable views performance statistics/debug messages via the Devel module'),
    '#description' => t("Check this to enable some Views query and performance statistics/debug messages <em>if Devel is installed</em>."),
    '#default_value' => $config->get('debug.output'),
  );

  $regions = array();
  $regions['watchdog'] = t('Watchdog');
  if (module_exists('devel')) {
    $regions['message'] = t('Devel message(dpm)');
    $regions['drupal_debug'] = t('Devel logging (tmp://drupal_debug.txt)');
  }

  $form['debug']['debug_region'] = array(
    '#type' => 'select',
    '#title' => t('Page region to output performance statistics/debug messages'),
    '#default_value' => $config->get('debug.region'),
    '#options' => $regions,
    '#states' => array(
      'visible' => array(
        ':input[name="views_devel_output"]' => array('checked' => TRUE),
      ),
    ),
  );

  $options = views_fetch_plugin_names('display_extender');
  if (!empty($options)) {
    $form['extenders'] = array(
      '#type' => 'details',
    );
    ;
    $form['extenders']['display_extenders'] = array(
      '#title' => t('Display extenders'),
      '#default_value' => views_get_enabled_display_extenders(),
      '#options' => $options,
      '#type' => 'checkboxes',
      '#description' => t('Select extensions of the views interface.')
    );
  }

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  $form['#submit'][] = 'views_ui_admin_settings_advanced_submit';

  return $form;
}

/**
 * Form builder submit handler; Handle submission the basic views settings..
 * @ingroup forms
 * @see system_settings_form()
 */
function views_ui_admin_settings_advanced_submit(&$form, &$form_state) {
  config('views.settings')
    ->set('skip_cache', $form_state['values']['skip_cache'])
    ->set('sql_signature', $form_state['values']['sql_signature'])
    ->set('no_javascript', $form_state['values']['no_javascript'])
    ->set('debug.output', $form_state['values']['debug_output'])
    ->set('debug.region', $form_state['values']['debug_region'])
    ->set('display_extenders', isset($form_state['values']['display_extenders']) ? $form_state['values']['display_extenders'] : array())
    ->save();
}

/**
 * Submit hook to clear the views cache.
 */
function views_ui_tools_clear_cache() {
  views_invalidate_cache();
  drupal_set_message(t('The cache has been cleared.'));
}

/**
 * Submit hook to clear Drupal's theme registry (thereby triggering
 * a templates rescan).
 */
function views_ui_config_item_form_rescan($form, &$form_state) {
  drupal_theme_rebuild();

  // The 'Theme: Information' page is about to be shown again. That page
  // analyzes the output of theme_get_registry(). However, this latter
  // function uses an internal cache (which was initialized before we
  // called drupal_theme_rebuild()) so it won't reflect the
  // current state of our theme registry. The only way to clear that cache
  // is to re-initialize the theme system:
  unset($GLOBALS['theme']);
  drupal_theme_initialize();

  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Override handler for views_ui_edit_display_form
 */
function views_ui_edit_display_form_change_theme($form, &$form_state) {
  // This is just a temporary variable.
  $form_state['view']->theme = $form_state['values']['theme'];

  views_ui_cache_set($form_state['view']);
  $form_state['rerender'] = TRUE;
  $form_state['rebuild'] = TRUE;
}

/**
 * Page callback for views tag autocomplete
 */
function views_ui_autocomplete_tag($string = '') {
  $matches = array();
  // get matches from default views:
  $views = views_get_all_views();
  foreach ($views as $view) {
    $tag = $view->get('tag');
    if ($tag && strpos($tag, $string) === 0) {
      $matches[$tag] = $tag;
      if (count($matches) >= 10) {
        break;
      }
    }
  }

  return new JsonResponse($matches);
}

function _views_weight_sort($a, $b) {
  if ($a['weight'] != $b['weight']) {
    return $a['weight'] < $b['weight'] ? -1 : 1;
  }
  if ($a['title'] != $b['title']) {
    return $a['title'] < $b['title'] ? -1 : 1;
  }

  return 0;
}

/**
 * Fetch a list of all base tables available
 *
 * @return
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_base_tables() {
  static $base_tables = array();
  if (empty($base_tables)) {
    $weights = array();
    $tables = array();
    $data = views_fetch_data();
    foreach ($data as $table => $info) {
      if (!empty($info['table']['base'])) {
        $tables[$table] = array(
          'title' => $info['table']['base']['title'],
          'description' => !empty($info['table']['base']['help']) ? $info['table']['base']['help'] : '',
          'weight' => !empty($info['table']['base']['weight']) ? $info['table']['base']['weight'] : 0,
        );
      }
    }
    uasort($tables, '_views_weight_sort');
    $base_tables = $tables;
  }

  return $base_tables;
}

function _views_sort_types($a, $b) {
  $a_group = drupal_strtolower($a['group']);
  $b_group = drupal_strtolower($b['group']);
  if ($a_group != $b_group) {
    return $a_group < $b_group ? -1 : 1;
  }

  $a_title = drupal_strtolower($a['title']);
  $b_title = drupal_strtolower($b['title']);
  if ($a_title != $b_title) {
    return $a_title < $b_title ? -1 : 1;
  }

  return 0;
}

/**
 * Fetch a list of all fields available for a given base type.
 *
 * @param (array|string) $base
 *   A list or a single base_table, for example node.
 * @param string $type
 *   The handler type, for example field or filter.
 * @param bool $grouping
 *   Should the result grouping by its 'group' label.
 * @param string $sub_type
 *   An optional sub type. E.g. Allows making an area plugin available for
 *   header only, instead of header, footer, and empty regions.
 *
 * @return array
 *   A keyed array of in the form of 'base_table' => 'Description'.
 */
function views_fetch_fields($base, $type, $grouping = FALSE, $sub_type = NULL) {
  static $fields = array();
  if (empty($fields)) {
    $data = views_fetch_data();
    $start = microtime(TRUE);
    // This constructs this ginormous multi dimensional array to
    // collect the important data about fields. In the end,
    // the structure looks a bit like this (using nid as an example)
    // $strings['nid']['filter']['title'] = 'string'.
    //
    // This is constructed this way because the above referenced strings
    // can appear in different places in the actual data structure so that
    // the data doesn't have to be repeated a lot. This essentially lets
    // each field have a cheap kind of inheritance.

    foreach ($data as $table => $table_data) {
      $bases = array();
      $strings = array();
      $skip_bases = array();
      foreach ($table_data as $field => $info) {
        // Collect table data from this table
        if ($field == 'table') {
          // calculate what tables this table can join to.
          if (!empty($info['join'])) {
            $bases = array_keys($info['join']);
          }
          // And it obviously joins to itself.
          $bases[] = $table;
          continue;
        }
        foreach (array('field', 'sort', 'filter', 'argument', 'relationship', 'area') as $key) {
          if (!empty($info[$key])) {
            if ($grouping && !empty($info[$key]['no group by'])) {
              continue;
            }
            if ($sub_type && isset($info[$key]['sub_type']) && (!in_array($sub_type, (array) $info[$key]['sub_type']))) {
              continue;
            }
            if (!empty($info[$key]['skip base'])) {
              foreach ((array) $info[$key]['skip base'] as $base_name) {
                $skip_bases[$field][$key][$base_name] = TRUE;
              }
            }
            elseif (!empty($info['skip base'])) {
              foreach ((array) $info['skip base'] as $base_name) {
                $skip_bases[$field][$key][$base_name] = TRUE;
              }
            }
            foreach (array('title', 'group', 'help', 'base', 'aliases') as $string) {
              // First, try the lowest possible level
              if (!empty($info[$key][$string])) {
                $strings[$field][$key][$string] = $info[$key][$string];
              }
              // Then try the field level
              elseif (!empty($info[$string])) {
                $strings[$field][$key][$string] = $info[$string];
              }
              // Finally, try the table level
              elseif (!empty($table_data['table'][$string])) {
                $strings[$field][$key][$string] = $table_data['table'][$string];
              }
              else {
                if ($string != 'base' && $string != 'base') {
                  $strings[$field][$key][$string] = t("Error: missing @component", array('@component' => $string));
                }
              }
            }
          }
        }
      }
      foreach ($bases as $base_name) {
        foreach ($strings as $field => $field_strings) {
          foreach ($field_strings as $type_name => $type_strings) {
            if (empty($skip_bases[$field][$type_name][$base_name])) {
              $fields[$base_name][$type_name]["$table.$field"] = $type_strings;
            }
          }
        }
      }
    }
  }

  // If we have an array of base tables available, go through them
  // all and add them together. Duplicate keys will be lost and that's
  // Just Fine.
  if (is_array($base)) {
    $strings = array();
    foreach ($base as $base_table) {
      if (isset($fields[$base_table][$type])) {
        $strings += $fields[$base_table][$type];
      }
    }
    uasort($strings, '_views_sort_types');
    return $strings;
  }

  if (isset($fields[$base][$type])) {
    uasort($fields[$base][$type], '_views_sort_types');
    return $fields[$base][$type];
  }
  return array();
}

/**
 * #process callback for a button; determines if a button is the form's triggering element.
 *
 * The Form API has logic to determine the form's triggering element based on
 * the data in $_POST. However, it only checks buttons based on a single #value
 * per button. This function may be added to a button's #process callbacks to
 * extend button click detection to support multiple #values per button. If the
 * data in $_POST matches any value in the button's #values array, then the
 * button is detected as having been clicked. This can be used when the value
 * (label) of the same logical button may be different based on context (e.g.,
 * "Apply" vs. "Apply and continue").
 *
 * @see _form_builder_handle_input_element()
 * @see _form_button_was_clicked()
 */
function views_ui_form_button_was_clicked($element, &$form_state) {
  $process_input = empty($element['#disabled']) && ($form_state['programmed'] || ($form_state['process_input'] && (!isset($element['#access']) || $element['#access'])));
  if ($process_input && !isset($form_state['triggering_element']) && !empty($element['#is_button']) && isset($form_state['input'][$element['#name']]) && isset($element['#values']) && in_array($form_state['input'][$element['#name']], $element['#values'], TRUE)) {
    $form_state['triggering_element'] = $element;
  }
  return $element;
}

/**
 * List all instances of fields on any views.
 *
 * Therefore it builds up a table of each field which is used in any view.
 *
 * @see field_ui_fields_list()
 */
function views_ui_field_list() {
  $views = views_get_all_views();

  // Fetch all fieldapi fields which are used in views
  // Therefore search in all views, displays and handler-types.
  $fields = array();
  $handler_types = ViewExecutable::viewsHandlerTypes();
  foreach ($views as $view) {
    $executable = $view->get('executable');
    $executable->initDisplay();
    foreach ($executable->displayHandlers as $display_id => $display) {
      if ($executable->setDisplay($display_id)) {
        foreach ($handler_types as $type => $info) {
          foreach ($executable->getItems($type, $display_id) as $item) {
            $data = views_fetch_data($item['table']);
            if (isset($data[$item['field']]) && isset($data[$item['field']][$type])
              && $data = $data[$item['field']][$type]) {
              // The final check that we have a fieldapi field now.
              if (isset($data['field_name'])) {
                $fields[$data['field_name']][$view->get('name')] = $view->get('name');
              }
            }
          }
        }
      }
    }
  }

  $header = array(t('Field name'), t('Used in'));
  $rows = array();
  foreach ($fields as $field_name => $views) {

    $rows[$field_name]['data'][0] = check_plain($field_name);
    foreach ($views as $view) {
      $rows[$field_name]['data'][1][] = l($view, "admin/structure/views/view/$view");
    }
    $rows[$field_name]['data'][1] = implode(', ', $rows[$field_name]['data'][1]);
  }

  // Sort rows by field name.
  ksort($rows);
  $output = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No fields have been used in views yet.'),
  );

  return $output;
}

/**
 * Lists all plugins and what enabled Views use them.
 */
function views_ui_plugin_list() {
  $rows = views_plugin_list();
  foreach ($rows as &$row) {
    // Link each view name to the view itself.
    foreach ($row['views'] as $row_name => $view) {
      $row['views'][$row_name] = l($view, "admin/structure/views/view/$view");
    }
    $row['views'] = implode(', ', $row['views']);
  }

  // Sort rows by field name.
  ksort($rows);
  return array(
    '#theme' => 'table',
    '#header' => array(t('Type'), t('Name'), t('Provided by'), t('Used in')),
    '#rows' => $rows,
    '#empty' => t('There are no enabled views.'),
  );
}
