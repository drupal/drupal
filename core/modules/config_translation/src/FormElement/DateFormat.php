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
    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $description = $this->t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://php.net/manual/function.date.php'));
    $format = $this->t('Displayed as %date_format', array('%date_format' => $date_formatter->format((int) $_SERVER['REQUEST_TIME'], 'custom', $translation_config)));

    return [
      '#type' => 'textfield',
      '#description' => $description,
      '#field_suffix' => ' <small data-drupal-date-formatter="preview">' . $format . '</small>',
      '#attributes' => [
        'data-drupal-date-formatter' => 'source',
      ],
      '#attached' => [
        'drupalSettings' => array('dateFormats' => $date_formatter->getSampleDateFormats($translation_language->getId())),
      ],
    ] + parent::getTranslationElement($translation_language, $source_config, $translation_config);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormAttributes() {
    return ['#attached' => ['library' => ['system/drupal.system.date']]];
  }

}
