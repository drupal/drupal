<?php

namespace Drupal\views_ui\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements trusted callbacks for views_ui.
 *
 * @package Drupal\views_ui\Form
 */
class ViewsUiTrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #process callback for ViewUI::getStandardButtons().
   *
   * The Form API has logic to determine the form's triggering element based on
   * the data in POST. However, it only checks buttons based on a single #value
   * per button. This function may be added to a button's #process callbacks to
   * extend button click detection to support multiple #values per button. If
   * the data in POST matches any value in the button's #values array, then the
   * button is detected as having been clicked. This can be used when the value
   * (label) of the same logical button may be different based on context (e.g.,
   * "Apply" vs. "Apply and continue").
   */
  public static function formButtonClicked(array &$element, FormStateInterface $form_state, array &$form) {
    $user_input = $form_state->getUserInput();
    $process_input = empty($element['#disabled']) && ($form_state->isProgrammed() || ($form_state->isProcessingInput() && (!isset($element['#access']) || $element['#access'])));
    if ($process_input && !$form_state->getTriggeringElement() && !empty($element['#is_button']) && isset($user_input[$element['#name']]) && isset($element['#values']) && in_array($user_input[$element['#name']], array_map('strval', $element['#values']), TRUE)) {
      $form_state->setTriggeringElement($element);
    }
    return $element;
  }

  /**
   * Implements #process callback for views_ui_add_ajax_trigger()
   *
   * Processes a non-JavaScript fallback submit button to limit its
   * validation errors.
   */
  public static function addLimitedValidation(array &$element, FormStateInterface $form_state, array &$form) {
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
    // was clicked, the form API workflow in
    // \Drupal::formBuilder()->doBuildForm() will have already copied it to
    // $form_state->getTriggeringElement() before our #process function is run.
    // So we need to make the same modifications in $form_state as we did to
    // the element itself, to ensure that #limit_validation_errors will
    // actually be set in the correct place.
    $clicked_button = &$form_state->getTriggeringElement();
    if ($clicked_button && $clicked_button['#name'] == $element['#name'] && $clicked_button['#value'] == $element['#value']) {
      $clicked_button['#limit_validation_errors'] = $element['#limit_validation_errors'];
    }

    return $element;
  }

  /**
   * Implements #after_build callback for views_ui_add_ajax_trigger()
   */
  public static function addAjaxWrapper(array &$element, FormStateInterface $form_state) {
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
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return [
      'formButtonClicked',
      'addLimitedValidation',
      'addAjaxWrapper',
    ];
  }

}
