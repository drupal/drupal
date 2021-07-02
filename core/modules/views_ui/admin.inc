<?php

/**
 * @file
 * Provides the Views' administrative interface.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views_ui\Form\ViewsUiTrustedFormCallbacks;

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
    '#process' => array_merge([[ViewsUiTrustedFormCallbacks::class, 'addLimitedValidation']], $element_info->getInfoProperty('submit', '#process', [])),
    // Add an after-build function that inserts a wrapper around the region of
    // the form that needs to be refreshed by AJAX (so that the AJAX system can
    // detect and dynamically update it). This is done in #after_build because
    // it's a convenient place where we have automatic access to the complete
    // form array, but also to minimize the chance that the HTML we add will
    // get clobbered by code that runs after we have added it.
    '#after_build' => array_merge($element_info->getInfoProperty('submit', '#after_build', []), [[ViewsUiTrustedFormCallbacks::class, 'addAjaxWrapper']]),
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
 * Implements #process callback.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use
 *   Drupal\views_ui\Form\TrustedFormCallbacks::addLimitedValidation()
 *   instead.
 * @see https://www.drupal.org/project/drupal/issues/2966711
 * @todo correct [at]see to use change record url above.
 */
function views_ui_add_limited_validation(&$element, FormStateInterface $form_state, $form) {
  return ViewsUiTrustedFormCallbacks::addLimitedValidation($element, $form_state, $form);
}

/**
 * Implements #after_build callback.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0.
 *   Use Drupal\views_ui\Form\TrustedFormCallbacks::addAjaxWrapper()
 *   instead.
 * @see https://www.drupal.org/project/drupal/issues/2966711
 * @todo correct [at]see to use change record url above.
 */
function views_ui_add_ajax_wrapper(array &$element, FormStateInterface $form_state) {
  return ViewsUiTrustedFormCallbacks::addAjaxWrapper($element, $form_state);
}

/**
 * Updates a part of the add view form via AJAX.
 *
 * @return
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
 * Add a <select> dropdown for a given section, allowing the user to
 * change whether this info is stored on the default display or on
 * the current display.
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
    $form['progress']['#markup'] = '<div id="views-progress-indicator" class="views-progress-indicator">' . t('@current of @total', ['@current' => $form_progress['current'], '@total' => $form_progress['total']]) . '</div>';
    $form['progress']['#weight'] = -1001;
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
    // @TODO: Translators may need more context than this.
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
 * Create the menu path for one of our standard AJAX forms based upon known
 * information about the form.
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
 * Implements #process callback.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use
 *   Drupal\views_ui\Form\TrustedFormCallbacks::formButtonClicked()
 *   instead.
 * @see https://www.drupal.org/project/drupal/issues/2966711
 * @todo correct [at]see to use change record url above.
 */
function views_ui_form_button_was_clicked(&$element, FormStateInterface $form_state, &$form) {
  return ViewsUiTrustedFormCallbacks::formButtonClicked($element, $form_state, $form);
}
