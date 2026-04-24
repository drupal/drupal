<?php

/**
 * @file
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ViewsFormAjaxHelperTrait;
use Drupal\views\ViewsFormHelperTrait;

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
 * @param array $wrapping_element
 *   The element whose child will server as the AJAX trigger. For example, if
 *   $form['some_wrapper']['triggering_element'] represents the element which
 *   will trigger the AJAX behavior, you would pass $form['some_wrapper'] for
 *   this parameter.
 * @param string $trigger_key
 *   The key within the wrapping element that identifies which of its children
 *   serves as the AJAX trigger. In the above example, you would pass
 *   'triggering_element' for this parameter.
 * @param array $refresh_parents
 *   An array of parent keys that point to the part of the form that will be
 *   refreshed by AJAX. For example, if triggering the AJAX behavior should
 *   cause $form['dynamic_content']['section'] to be refreshed, you would pass
 *   ['dynamic_content', 'section'] for this parameter.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is
 *   no public replacement for this function. Form classes can use the
 *   protected method \Drupal\views\ViewsFormAjaxHelperTrait::addAjaxTrigger().
 *
 * @see https://www.drupal.org/node/3040111
 */
function views_ui_add_ajax_trigger(&$wrapping_element, $trigger_key, $refresh_parents): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no public replacement for this function. Form classes can use the protected method \Drupal\views_ui\ViewsFormAjaxHelperTrait::addAjaxTrigger(). See https://www.drupal.org/node/3040111', E_USER_DEPRECATED);
  _views_ui_wrap_views_form_helper_traits()->addAjaxTriggerWrapper($wrapping_element, $trigger_key, $refresh_parents);
}

/**
 * Limits validation errors for a non-JavaScript fallback submit button.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormAjaxHelperTrait trait and call the
 *   addLimitedValidation() method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_add_limited_validation($element, FormStateInterface $form_state) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement for this function. Form classes can use the \Drupal\views\ViewsFormAjaxHelperTrait trait and call the addLimitedValidation() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  return _views_ui_wrap_views_form_helper_traits()::addLimitedValidation($element, $form_state);
}

/**
 * Adds a wrapper to a form region (for AJAX refreshes) after the build.
 *
 * This function inserts a wrapper around the region of the form that needs to
 * be refreshed by AJAX, based on information stored in the corresponding
 * submit button form element.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormAjaxHelperTrait trait and call the addAjaxWrapper()
 *   method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_add_ajax_wrapper($element, FormStateInterface $form_state) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement for this function. Form classes can use the \Drupal\views\ViewsFormAjaxHelperTrait trait and call the addAjaxWrapper() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  _views_ui_wrap_views_form_helper_traits()::addAjaxWrapper($element, $form_state);
}

/**
 * Updates a part of the add view form via AJAX.
 *
 * @return array
 *   The part of the form that has changed.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormAjaxHelperTrait trait and call the ajaxUpdateForm()
 *   method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_ajax_update_form($form, FormStateInterface $form_state) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement for this function. Form classes can use the \Drupal\views\ViewsFormAjaxHelperTrait trait and call the addAjaxWrapper() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  return _views_ui_wrap_views_form_helper_traits()->ajaxUpdateForm($form, $form_state);
}

/**
 * Non-JavaScript fallback for updating the add view form.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormAjaxHelperTrait trait and call the noJsSubmit()
 *   method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_nojs_submit($form, FormStateInterface $form_state): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement for this function. Form classes can use the \Drupal\views\ViewsFormAjaxHelperTrait trait and call the noJsSubmit() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  _views_ui_wrap_views_form_helper_traits()::noJsSubmit($form, $form_state);
}

/**
 * Adds an element to select either the default display or the current display.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   public replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormHelperTrait trait and call the
 *   standardDisplayDropdown() method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_standard_display_dropdown(&$form, FormStateInterface $form_state, $section): void {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no public replacement for this function. Form classes can use the \Drupal\views\ViewsFormHelperTrait trait and call the standardDisplayDropdown() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  _views_ui_wrap_views_form_helper_traits()->standardDisplayDropdownWrapper($form, $form_state, $section);
}

/**
 * Creates the menu path for a standard AJAX form given the form state.
 *
 * @return \Drupal\Core\Url
 *   The URL object pointing to the form URL.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   public replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormHelperTrait trait and call the buildFormUrl()
 *   method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_build_form_url(FormStateInterface $form_state) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no public replacement for this function. Form classes can use the \Drupal\views\ViewsFormHelperTrait trait and call the buildFormUrl() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  return _views_ui_wrap_views_form_helper_traits()->buildFormUrlWrapper($form_state);
}

/**
 * The #process callback for a button.
 *
 * Determines if a button is the form's triggering element.
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
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   public replacement for this function. Form classes can use the
 *   \Drupal\views\ViewsFormHelperTrait trait and call the
 *   formButtonWasClicked() method.
 *
 * @see https://www.drupal.org/node/3566774
 */
function views_ui_form_button_was_clicked($element, FormStateInterface $form_state) {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no public replacement for this function. Form classes can use the \Drupal\views\ViewsFormHelperTrait trait and call the formButtonWasClicked() method. See https://www.drupal.org/node/3566774', E_USER_DEPRECATED);
  return _views_ui_wrap_views_form_helper_traits()::formButtonWasClicked($element, $form_state);
}

/**
 * Provides a BC layer for procedural deprecated function.
 *
 * @return object
 *   A minimal class that only uses ViewsFormAjaxHelperTrait and
 *   ViewsFormHelperTrait traits and wraps the protected methods to make them
 *   accessible for the deprecated functions.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3040111
 */
function _views_ui_wrap_views_form_helper_traits(): object {
  @trigger_error(__FUNCTION__ . '() is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3040111', E_USER_DEPRECATED);
  return new class() {
    use ViewsFormAjaxHelperTrait;
    use ViewsFormHelperTrait;

    public function addAjaxTriggerWrapper(array &$wrapping_element, $trigger_key, array $refresh_parents): void {
      $this->addAjaxTrigger($wrapping_element, $trigger_key, $refresh_parents);
    }

    public function standardDisplayDropdownWrapper(array &$form, FormStateInterface $formState, string $section): void {
      $this->standardDisplayDropdown($form, $formState, $section);
    }

    public function buildFormUrlWrapper(FormStateInterface $formState): Url {
      return $this->buildFormUrl($formState);
    }

  };
}
