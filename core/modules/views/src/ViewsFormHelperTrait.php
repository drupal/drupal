<?php

declare(strict_types=1);

namespace Drupal\views;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides reusable code to be shared by Views forms.
 */
trait ViewsFormHelperTrait {

  use StringTranslationTrait;

  /**
   * Adds an element to select either the default or the current display.
   *
   * @param array $form
   *   The form render array to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   * @param string $section
   *   The section to which the display dropdown belongs.
   */
  protected function standardDisplayDropdown(array &$form, FormStateInterface $formState, string $section): void {
    $view = $formState->get('view');
    $displayId = $formState->get('display_id');
    $executable = $view->getExecutable();
    $displays = $executable->displayHandlers;
    $currentDisplay = $executable->display_handler;

    // @todo Move this to a separate function if it's needed on any forms that
    // don't have the display dropdown.
    $form['override'] = [
      '#prefix' => '<div class="views-override clearfix form--inline views-offset-top" data-drupal-views-offset="top">',
      '#suffix' => '</div>',
      '#weight' => -1000,
      '#tree' => TRUE,
    ];

    // Add the "2 of 3" progress indicator.
    if ($formProgress = $view->getFormProgress()) {
      $arguments = $form['#title']->getArguments() + [
        '@current' => $formProgress['current'],
        '@total' => $formProgress['total'],
      ];
      $form['#title'] = $this->t('Configure @type @current of @total: @item', $arguments);
    }

    // The dropdown should not be added when any of the following is true:
    // - this is the default display.
    // - there is no default shown and just one additional display (mostly
    //   page), and the current display is defaulted.
    if (
      $currentDisplay->isDefaultDisplay()
      || (
        $currentDisplay->isDefaulted($section)
        && !$this->getConfigFactory()->get('views.settings')->get('ui.show.default_display')
        && count($displays) <= 2
      )
    ) {
      return;
    }

    // Determine whether any other displays have overrides for this section.
    $sectionOverrides = FALSE;
    $sectionDefaulted = $currentDisplay->isDefaulted($section);
    foreach ($displays as $id => $display) {
      if ($id === 'default' || $id === $displayId) {
        continue;
      }
      if ($display && !$display->isDefaulted($section)) {
        $sectionOverrides = TRUE;
      }
    }

    $displayDropdown['default'] = ($sectionOverrides ? $this->t('All displays (except overridden)') : $this->t('All displays'));
    $displayDropdown[$displayId] = $this->t('This @display_type (override)', ['@display_type' => $currentDisplay->getPluginId()]);
    // Only display the revert option if we are in an overridden section.
    if (!$sectionDefaulted) {
      $displayDropdown['default_revert'] = $this->t('Revert to default');
    }

    $form['override']['dropdown'] = [
      '#type' => 'select',
      // @todo Translators may need more context than this.
      '#title' => $this->t('For'),
      '#options' => $displayDropdown,
    ];
    if ($currentDisplay->isDefaulted($section)) {
      $form['override']['dropdown']['#default_value'] = 'defaults';
    }
    else {
      $form['override']['dropdown']['#default_value'] = $displayId;
    }
  }

  /**
   * Creates the menu path for a standard AJAX form given the form state.
   *
   * @return \Drupal\Core\Url
   *   The URL object pointing to the form URL.
   */
  protected function buildFormUrl(FormStateInterface $formState): Url {
    $ajax = !$formState->get('ajax') ? 'nojs' : 'ajax';
    $name = $formState->get('view')->id();
    $formKey = $formState->get('form_key');
    $displayId = $formState->get('display_id');

    $formKey = str_replace('-', '_', $formKey);
    $routeName = "views_ui.form_$formKey";
    $routeParameters = [
      'js' => $ajax,
      'view' => $name,
      'display_id' => $displayId,
    ];
    $url = Url::fromRoute($routeName, $routeParameters);
    if ($type = $formState->get('type')) {
      $url->setRouteParameter('type', $type);
    }
    if ($id = $formState->get('id')) {
      $url->setRouteParameter('id', $id);
    }
    return $url;
  }

  /**
   * The #process callback for a button.
   *
   * Determines if a button is the form's triggering element.
   *
   * The Form API has logic to determine the form's triggering element based on
   * the data in POST. However, it only checks buttons based on a single #value
   * per button. This function may be added to a button's #process callbacks to
   * extend button click detection to support multiple #values per button. If
   * the data in POST matches any value in the button's #values array, then the
   * button is detected as having been clicked. This can be used when the value
   * (label) of the same logical button may be different based on context (e.g.,
   * "Apply" vs. "Apply and continue").
   *
   * @see \Drupal\Core\Form\FormBuilder::handleInputElement()
   * @see \Drupal\Core\Form\FormBuilder::buttonWasClicked()
   */
  public static function formButtonWasClicked(array $element, FormStateInterface $formState): array {
    $userInput = $formState->getUserInput();
    $processInput = empty($element['#disabled']) && ($formState->isProgrammed() || ($formState->isProcessingInput() && (!isset($element['#access']) || $element['#access'])));
    if ($processInput && !$formState->getTriggeringElement() && !empty($element['#is_button']) && isset($userInput[$element['#name']]) && isset($element['#values']) && in_array($userInput[$element['#name']], array_map('strval', $element['#values']), TRUE)) {
      $formState->setTriggeringElement($element);
    }
    return $element;
  }

  /**
   * Returns the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory service.
   */
  protected function getConfigFactory(): ConfigFactoryInterface {
    return \Drupal::service('config.factory');
  }

}
