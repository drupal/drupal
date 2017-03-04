<?php

namespace Drupal\config_translation\FormElement;

use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the date format element for the configuration translation interface.
 */
class DateFormat extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $description = $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.');
    $format = $this->t('Displayed as %date_format', ['%date_format' => $date_formatter->format(REQUEST_TIME, 'custom', $translation_config)]);

    return [
      '#type' => 'textfield',
      '#description' => $description,
      '#field_suffix' => ' <small data-drupal-date-formatter="preview">' . $format . '</small>',
      '#attributes' => [
        'data-drupal-date-formatter' => 'source',
      ],
      '#attached' => [
        'drupalSettings' => ['dateFormats' => $date_formatter->getSampleDateFormats($translation_language->getId())],
        'library' => ['system/drupal.system.date'],
      ],
    ] + parent::getTranslationElement($translation_language, $source_config, $translation_config);
  }

}
