<?php

/**
 * @file
 * Provides the Views' administrative interface.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
  $seen_ids = &drupal_static(__FUNCTION__ . ':seen_ids', []);
  $seen_buttons = &drupal_static(__FUNCTION__ . ':seen_buttons', []);

  // Add the AJAX behavior to the triggering element.
  $triggering_element = &$wrapping_element[$trigger_key];
  $triggering_element['#ajax']['callback'] = 'views_ui_ajax_update_form';

  // We do not use \Drupal\Component\Utility\Html::getUniqueId() to get an ID
  // for the AJAX wrapper, because it remembers IDs across AJAX requests (and
  // won't reuse them), but in our case we need to use the same ID from request
  // to request so that the wrapper can be recognized by the AJAX system and
  // its content can be dynamically updated. So instead, we will keep track of
  // duplicate IDs (within a single request) on our own, later in this function.
  $triggering_element['#ajax']['wrapper'] = 'edit-view-' . implode('-', $refresh_parents) . '-wrapper';

  // Add a submit button for users who do not have JavaScript enabled. It
  // should be displayed next to the triggering element on the form.
  $button_key = $trigger_key . '_trigger_update';
  $element_info = \Drupal::service('element_info');
  $wrapping_element[$button_key] = [
    '#type' => 'submit',
    // Hide this button when JavaScript is enabled.
    '#attributes' => ['class' => ['js-hide']],
    '#submit' => ['views_ui_nojs_submit'],
    // Add a process function to limit this button's validation errors to the
    // triggering element only. We have to do this in #process since until the
    // form API has added the #parents property to the triggering element for
    // us, we don't have any (easy) way to find out where its submitted values
    // will eventually appear in $form_state->getValues().
    '#process' => array_merge(['views_ui_add_limited_validation'], $element_info->getInfoProperty('submit', '#process', [])),
    // Add an after-build function that inserts a wrapper around the region of
    // the form that needs to be refreshed by AJAX (so that the AJAX system can
    // detect and dynamically update it). This is done in #after_build because
    // it's a convenient place where we have automatic access to the complete
    // form array, but also to minimize the chance that the HTML we add will
    // get clobbered by code that runs after we have added it.
    '#after_build' => array_merge($element_info->getInfoProperty('submit', '#after_build', []), ['views_ui_add_ajax_wrapper']),
  ];
  // Copy #weight and #access from the triggering element to the button, so
  // that the two elements will be displayed together.
  foreach (['#weight', '#access'] as $property) {
    if (isset($triggering_element[$property])) {
      $wrapping_element[$button_key][$property] = $triggering_element[$property];
    }
  }
  // For easiest integration with the form API and the testing framework, we
  // always give the button a unique #value, rather than playing around with
  // #name. We also cast the #title to string as we will use it as an array
  // key and it may be a TranslatableMarkup.
  $button_title = !empty($triggering_element['#title']) ? (string) $triggering_element['#title'] : $trigger_key;
  if (empty($seen_buttons[$button_title])) {
    $wrapping_element[$button_key]['#value'] = t('Update "@title" choice', [
      '@title' => $button_title,
    ]);
    $seen_buttons[$button_title] = 1;
  }
  else {
    $wrapping_element[$button_key]['#value'] = t('Update "@title" choice (@number)', [
      '@title' => $button_title,
      '@number' => ++$seen_buttons[$button_title],
    ]);
  }

  // Attach custom data to the triggering element and submit button, so we can
  // use it in both the process function and AJAX callback.
  $ajax_data = [
    'wrapper' => $triggering_element['#ajax']['wrapper'],
    'trigger_key' => $trigger_key,
    'refresh_parents' => $refresh_parents,
  ];
  $seen_ids[$triggering_element['#ajax']['wrapper']] = TRUE;
  $triggering_element['#views_ui_ajax_data'] = $ajax_data;
  $wrapping_element[$button_key]['#views_ui_ajax_data'] = $ajax_data;
}

/**
 * Processes a non-JavaScript fallback submit button to limit its validation errors.
 */
function views_ui_add_limited_validation($element, FormStateInterface $form_state) {
  // Retrieve the AJAX triggering element so we can determine its parents. (We
  // know it's at the same level of the complete form array as the submit
  // button, so all we have to do to find it is swap out the submit button's
  // last array parent.)
  $array_parents = $element['#array_parents'];
  array_pop($array_parents);
  $array_parents[] = $element['#views_ui_ajax_data']['trigger_key'];
  $ajax_triggering_element = NestedArray::getValue($form_state->getCompleteForm(), $array_parents);

  // Limit this button's validation to the AJAX triggering element, so it can
  // update the form for that change without requiring that the rest of the
  // form be filled out properly yet.
  $element['#limit_validation_errors'] = [$ajax_triggering_element['#parents']];

  // If we are in the process of a form submission and this is the button that
  // was clicked, the form API workflow in \Drupal::formBuilder()->doBuildForm()
  // will have already copied it to $form_state->getTriggeringElement() before
  // our #process function is run. So we need to make the same modifications in
  // $form_state as we did to the element itself, to ensure that
  // #limit_validation_errors will actually be set in the correct place.
  $clicked_button = &$form_state->getTriggeringElement();
  if ($clicked_button && $clicked_button['#name'] == $element['#name'] && $clicked_button['#value'] == $element['#value']) {
    $clicked_button['#limit_validation_errors'] = $element['#limit_validation_errors'];
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
function views_ui_add_ajax_wrapper($element, FormStateInterface $form_state) {
  // Find the region of the complete form that needs to be refreshed by AJAX.
  // This was earlier stored in a property on the element.
  $complete_form = &$form_state->getCompleteForm();
  $refresh_parents = $element['#views_ui_ajax_data']['refresh_parents'];
  $refresh_element = NestedArray::getValue($complete_form, $refresh_parents);

  // The HTML ID that AJAX expects was also stored in a property on the
  // element, so use that information to insert the wrapper <div> here.
  $id = $element['#views_ui_ajax_data']['wrapper'];
  $refresh_element += [
    '#prefix' => '',
    '#suffix' => '',
  ];
  $refresh_element['#prefix'] = '<div id="' . $id . '" class="views-ui-ajax-wrapper">' . $refresh_element['#prefix'];
  $refresh_element['#suffix'] .= '</div>';

  // Copy the element that needs to be refreshed back into the form, with our
  // modifications to it.
  NestedArray::setValue($complete_form, $refresh_parents, $refresh_element);

  return $element;
}

/**
 * Updates a part of the add view form via AJAX.
 *
 * @return array
 *   The part of the form that has changed.
 */
function views_ui_ajax_update_form($form, FormStateInterface $form_state) {
  // The region that needs to be updated was stored in a property of the
  // triggering element by views_ui_add_ajax_trigger(), so all we have to do is
  // retrieve that here.
  return NestedArray::getValue($form, $form_state->getTriggeringElement()['#views_ui_ajax_data']['refresh_parents']);
}

/**
 * Non-JavaScript fallback for updating the add view form.
 */
function views_ui_nojs_submit($form, FormStateInterface $form_state) {
  $form_state->setRebuild();
}

/**
 * Adds an element to select either the default display or the current display.
 */
function views_ui_standard_display_dropdown(&$form, FormStateInterface $form_state, $section) {
  $view = $form_state->get('view');
  $display_id = $form_state->get('display_id');
  $executable = $view->getExecutable();
  $displays = $executable->displayHandlers;
  $current_display = $executable->display_handler;

  // @todo Move this to a separate function if it's needed on any forms that
  // don't have the display dropdown.
  $form['override'] = [
    '#prefix' => '<div class="views-override clearfix form--inline views-offset-top" data-drupal-views-offset="top">',
    '#suffix' => '</div>',
    '#weight' => -1000,
    '#tree' => TRUE,
  ];

  // Add the "2 of 3" progress indicator.
  if ($form_progress = $view->getFormProgress()) {
    $arguments = $form['#title']->getArguments() + ['@current' => $form_progress['current'], '@total' => $form_progress['total']];
    $form['#title'] = t('Configure @type @current of @total: @item', $arguments);
  }

  // The dropdown should not be added when :
  // - this is the default display.
  // - there is no default shown and just one additional display (mostly page)
  //   and the current display is defaulted.
  if ($current_display->isDefaultDisplay() || ($current_display->isDefaulted($section) && !\Drupal::config('views.settings')->get('ui.show.default_display') && count($displays) <= 2)) {
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
  $display_dropdown[$display_id] = t('This @display_type (override)', ['@display_type' => $current_display->getPluginId()]);
  // Only display the revert option if we are in an overridden section.
  if (!$section_defaulted) {
    $display_dropdown['default_revert'] = t('Revert to default');
  }

  $form['override']['dropdown'] = [
    '#type' => 'select',
    // @todo Translators may need more context than this.
    '#title' => t('For'),
    '#options' => $display_dropdown,
  ];
  if ($current_display->isDefaulted($section)) {
    $form['override']['dropdown']['#default_value'] = 'defaults';
  }
  else {
    $form['override']['dropdown']['#default_value'] = $display_id;
  }

}

/**
 * Creates the menu path for a standard AJAX form given the form state.
 *
 * @return \Drupal\Core\Url
 *   The URL object pointing to the form URL.
 */
function views_ui_build_form_url(FormStateInterface $form_state) {
  $ajax = !$form_state->get('ajax') ? 'nojs' : 'ajax';
  $name = $form_state->get('view')->id();
  $form_key = $form_state->get('form_key');
  $display_id = $form_state->get('display_id');

  $form_key = str_replace('-', '_', $form_key);
  $route_name = "views_ui.form_{$form_key}";
  $route_parameters = [
    'js' => $ajax,
    'view' => $name,
    'display_id' => $display_id,
  ];
  $url = Url::fromRoute($route_name, $route_parameters);
  if ($type = $form_state->get('type')) {
    $url->setRouteParameter('type', $type);
  }
  if ($id = $form_state->get('id')) {
    $url->setRouteParameter('id', $id);
  }
  return $url;
}

/**
 * #process callback for a button; determines if a button is the form's triggering element.
 *
 * The Form API has logic to determine the form's triggering element based on
 * the data in POST. However, it only checks buttons based on a single #value
 * per button. This function may be added to a button's #process callbacks to
 * extend button click detection to support multiple #values per button. If the
 * data in POST matches any value in the button's #values array, then the
 * button is detected as having been clicked. This can be used when the value
 * (label) of the same logical button may be different based on context (e.g.,
 * "Apply" vs. "Apply and continue").
 *
 * @see _form_builder_handle_input_element()
 * @see _form_button_was_clicked()
 */
function views_ui_form_button_was_clicked($element, FormStateInterface $form_state) {
  $user_input = $form_state->getUserInput();
  $process_input = empty($element['#disabled']) && ($form_state->isProgrammed() || ($form_state->isProcessingInput() && (!isset($element['#access']) || $element['#access'])));
  if ($process_input && !$form_state->getTriggeringElement() && !empty($element['#is_button']) && isset($user_input[$element['#name']]) && isset($element['#values']) && in_array($user_input[$element['#name']], array_map('strval', $element['#values']), TRUE)) {
    $form_state->setTriggeringElement($element);
  }
  return $element;
}
