<?php

declare(strict_types=1);

namespace Drupal\views;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides reusable code to be shared by Views Ajax forms.
 */
trait ViewsFormAjaxHelperTrait {

  use StringTranslationTrait;

  /**
   * Memory cache for processed buttons.
   *
   * @var array<string, int>
   */
  protected static array $ajaxTriggerButtons = [];

  /**
   * Converts a form element in the new view wizard to be AJAX-enabled.
   *
   * This function takes a form element and adds AJAX behaviors to it such that
   * changing it triggers another part of the form to update automatically. It
   * also adds a submit-button to the form that appears next to the triggering
   * element, and that duplicates its functionality for users who do not have
   * JavaScript enabled (the button is automatically hidden for users who do
   * have JavaScript).
   *
   * To use this function, call it directly from your form builder function
   * immediately after you have defined the form element that will serve as the
   * JavaScript trigger. Calling it elsewhere (such as in hook_form_alter()) may
   * mean that the non-JavaScript fallback button does not appear in the correct
   * place in the form.
   *
   * @param array $wrappingElement
   *   The element whose child will server as the AJAX trigger. For example, if
   *   $form['some_wrapper']['triggering_element'] represents the element which
   *   will trigger the AJAX behavior, you would pass $form['some_wrapper'] for
   *   this parameter.
   * @param string $triggerKey
   *   The key within the wrapping element that identifies which of its children
   *   serves as the AJAX trigger. In the above example, you would pass
   *   'triggering_element' for this parameter.
   * @param string[] $refreshParents
   *   An array of parent keys that point to the part of the form that AJAX will
   *   refresh. For example, if triggering the AJAX behavior should cause
   *   $form['dynamic_content']['section'] to be refreshed, you would pass
   *   ['dynamic_content', 'section'] for this parameter.
   */
  protected function addAjaxTrigger(array &$wrappingElement, string $triggerKey, array $refreshParents): void {
    // Add the AJAX behavior to the triggering element.
    $triggeringElement = &$wrappingElement[$triggerKey];
    $triggeringElement['#ajax']['callback'] = static::class . ':ajaxUpdateForm';

    // We do not use \Drupal\Component\Utility\Html::getUniqueId() to get an ID
    // for the AJAX wrapper. It remembers IDs across AJAX requests (and won't
    // reuse them). But in our case we need to use the same ID from request to
    // request so that the wrapper can be recognized by the AJAX system and its
    // content can be dynamically updated. So instead, we will keep track of
    // duplicate IDs (within a single request) on our own, later in this method.
    $triggeringElement['#ajax']['wrapper'] = 'edit-view-' . implode('-', $refreshParents) . '-wrapper';

    // Add a submit-button for users who do not have JavaScript enabled. It
    // should be displayed next to the triggering element on the form.
    $buttonKey = $triggerKey . '_trigger_update';
    $wrappingElement[$buttonKey] = [
      '#type' => 'submit',
      // Hide this button when JavaScript is enabled.
      '#attributes' => ['class' => ['js-hide']],
      '#submit' => [[static::class, 'noJsSubmit']],
      // Add a process function to limit this button's validation errors to the
      // triggering element only. We have to do this in #process since until the
      // form API has added the #parents property to the triggering element for
      // us, we don't have any (easy) way to find out where its submitted values
      // will eventually appear in FormStateInterface->getValues().
      '#process' => [
        [static::class, 'addLimitedValidation'],
        ...$this->getElementInfo()->getInfoProperty('submit', '#process', []),
      ],
      // Add an after-build function that inserts a wrapper around the region of
      // the form that needs to be refreshed by AJAX (so that the AJAX system
      // can detect and dynamically update it). This is done in #after_build
      // because it's a convenient place where we have automatic access to the
      // complete form array, but also to minimize the chance that the HTML we
      // add will get clobbered by code that runs after we have added it.
      '#after_build' => [
        ...$this->getElementInfo()->getInfoProperty('submit', '#after_build', []),
        [static::class, 'addAjaxWrapper'],
      ],
    ];
    // Copy #weight and #access from the triggering element to the button so
    // that the two elements will be displayed together.
    foreach (['#weight', '#access'] as $property) {
      if (isset($triggeringElement[$property])) {
        $wrappingElement[$buttonKey][$property] = $triggeringElement[$property];
      }
    }
    // For the easiest integration with the form API and the testing framework,
    // we always give the button a unique #value, rather than playing around
    // with #name. We also cast the #title to string as we will use it as an
    // array key, and it may be a TranslatableMarkup.
    $buttonTitle = !empty($triggeringElement['#title']) ? (string) $triggeringElement['#title'] : $triggerKey;
    if (empty(static::$ajaxTriggerButtons[$buttonTitle])) {
      $wrappingElement[$buttonKey]['#value'] = $this->t('Update "@title" choice', [
        '@title' => $buttonTitle,
      ]);
      static::$ajaxTriggerButtons[$buttonTitle] = 1;
    }
    else {
      $wrappingElement[$buttonKey]['#value'] = $this->t('Update "@title" choice (@number)', [
        '@title' => $buttonTitle,
        '@number' => ++static::$ajaxTriggerButtons[$buttonTitle],
      ]);
    }

    // Attach custom data to the triggering element and submit button, so we can
    // use it in both the process function and AJAX callback.
    $ajaxData = [
      'wrapper' => $triggeringElement['#ajax']['wrapper'],
      'trigger_key' => $triggerKey,
      'refresh_parents' => $refreshParents,
    ];
    $triggeringElement['#views_ui_ajax_data'] = $ajaxData;
    $wrappingElement[$buttonKey]['#views_ui_ajax_data'] = $ajaxData;
  }

  /**
   * Limits validation errors for a non-JavaScript fallback submit button.
   *
   * @param array $element
   *   The form element render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @return array
   *   Render array.
   */
  public static function addLimitedValidation(array $element, FormStateInterface $formState): array {
    // Retrieve the AJAX triggering element so we can determine its parents. (We
    // know it's at the same level of the complete form array as the
    // submit-button, so all we have to do to find it is swap out the
    // submit-button's last array parent.)
    $arrayParents = $element['#array_parents'];
    array_pop($arrayParents);
    $arrayParents[] = $element['#views_ui_ajax_data']['trigger_key'];
    $ajaxTriggeringElement = NestedArray::getValue($formState->getCompleteForm(), $arrayParents);

    // Limit this button's validation to the AJAX triggering element, so it can
    // update the form for that change without requiring that the rest of the
    // form be already filled out properly.
    $element['#limit_validation_errors'] = [$ajaxTriggeringElement['#parents']];

    // If we are in the process of a form submission and this is the button that
    // was clicked, the form API workflow in
    // \Drupal::formBuilder()->doBuildForm() will have already copied it to
    // $formState->getTriggeringElement() before our #process function is run.
    // So we need to make the same modifications in $formState as we did to the
    // element itself to ensure that #limit_validation_errors will actually be
    // set in the correct place.
    $clickedButton = &$formState->getTriggeringElement();
    if ($clickedButton && $clickedButton['#name'] == $element['#name'] && $clickedButton['#value'] == $element['#value']) {
      $clickedButton['#limit_validation_errors'] = $element['#limit_validation_errors'];
    }

    return $element;
  }

  /**
   * Adds a wrapper to a form region (for AJAX refreshes) after the build.
   *
   * This function inserts a wrapper around the region of the form that needs to
   * be refreshed by AJAX, based on information stored in the corresponding
   * submit-button form element.
   *
   * @param array $element
   *   The form element render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @return array
   *   Render array.
   */
  public static function addAjaxWrapper(array $element, FormStateInterface $formState): array {
    // Find the region of the complete form that needs to be refreshed by AJAX.
    // This was earlier stored in a property on the element.
    $completeForm = &$formState->getCompleteForm();
    $refreshParents = $element['#views_ui_ajax_data']['refresh_parents'];
    $refreshElement = NestedArray::getValue($completeForm, $refreshParents);

    // The HTML ID that AJAX expects was also stored in a property on the
    // element, so use that information to insert the wrapper <div> here.
    $id = $element['#views_ui_ajax_data']['wrapper'];
    $refreshElement += [
      '#prefix' => '',
      '#suffix' => '',
    ];
    $refreshElement['#prefix'] = '<div id="' . $id . '" class="views-ui-ajax-wrapper">' . $refreshElement['#prefix'];
    $refreshElement['#suffix'] .= '</div>';

    // Copy the element that needs to be refreshed back into the form, with our
    // modifications to it.
    NestedArray::setValue($completeForm, $refreshParents, $refreshElement);

    return $element;
  }

  /**
   * Provides a triggering element Ajax callback.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @return array
   *   Render array.
   */
  public function ajaxUpdateForm(array $form, FormStateInterface $formState): array {
    // The region that needs to be updated was stored in a property of the
    // triggering element by self::addAjaxTrigger(), so all we have to do is
    // retrieve that here.
    // @see \Drupal\views\ViewsFormAjaxHelperTrait::addAjaxTrigger()
    return NestedArray::getValue($form, $formState->getTriggeringElement()['#views_ui_ajax_data']['refresh_parents']);
  }

  /**
   * Provides a callback for non-JavaScript submit.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  public static function noJsSubmit(array $form, FormStateInterface $formState): void {
    $formState->setRebuild();
  }

  /**
   * Returns the element info plugin manager.
   *
   * @return \Drupal\Core\Render\ElementInfoManagerInterface
   *   The element info plugin manager.
   */
  protected function getElementInfo(): ElementInfoManagerInterface {
    return \Drupal::service('plugin.manager.element_info');
  }

}
