<?php

/**
 * @file
 * Contains \Drupal\config_translation\FormElement\DateFormat.
 */

namespace Drupal\config_translation\FormElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the date format element for the configuration translation interface.
 */
class DateFormat extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    $description = $this->t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://php.net/manual/function.date.php'));
    $format = $this->t('Displayed as %date_format', array('%date_format' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', $translation_config)));

    return array(
      '#type' => 'textfield',
      '#description' => $description,
      '#field_suffix' => ' <div class="edit-date-format-suffix"><small id="edit-date-format-suffix">' . $format . '</small></div>',
      '#ajax' => array(
        'callback' => 'Drupal\config_translation\FormElement\DateFormat::ajaxSample',
        'event' => 'keyup',
        'progress' => array('type' => 'throbber', 'message' => NULL),
      ),
    ) + parent::getTranslationElement($translation_language, $source_config, $translation_config);
  }

  /**
   * Ajax callback to render a sample of the input date format.
   *
   * @param array $form
   *   Form API array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state information.
   *
   * @return AjaxResponse
   *   Ajax response with the rendered sample date using the given format. If
   *   the given format cannot be identified or was empty, the response will
   *   be empty as well.
   */
  public static function ajaxSample(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $format_value = NestedArray::getValue($form_state->getValues(), $form_state->getTriggeringElement()['#array_parents']);
    if (!empty($format_value)) {
      // Format the date with a custom date format with the given pattern.
      // The object is not instantiated in an Ajax context, so $this->t()
      // cannot be used here.
      $format = t('Displayed as %date_format', array('%date_format' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', $format_value)));

      // Return a command instead of a string, since the Ajax framework
      // automatically prepends an additional empty DIV element for a string,
      // which breaks the layout.
      $response->addCommand(new ReplaceCommand('#edit-date-format-suffix', '<small id="edit-date-format-suffix">' . $format . '</small>'));
    }

    return $response;
  }

}
