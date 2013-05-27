<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatFormBase.
 */

namespace Drupal\system\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormInterface;
use Drupal\Component\Utility\String;

/**
 * Provides a base form for date formats.
 */
abstract class DateFormatFormBase implements FormInterface {

  /**
   * The date pattern type.
   *
   * @var string
   */
  protected $patternType;

  /**
   * Constructs a new date format form.
   */
  public function __construct() {
    $date = new DrupalDateTime();
    $this->patternType = $date->canUseIntl() ? DrupalDateTime::INTL : DrupalDateTime::PHP;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $formats = system_get_date_formats();
    $format = trim($form_state['values']['date_format_pattern']);

    // The machine name field should already check to see if the requested
    // machine name is available. Regardless of machine_name or human readable
    // name, check to see if the provided pattern exists.
    if (!empty($formats) && in_array($format, array_values($formats)) && (!isset($form_state['values']['date_format_id']) || $form_state['values']['date_format_id'] != $formats[$format]['date_format_id'])) {
      form_set_error('date_format', t('This format already exists. Enter a unique format string.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $format = array();
    $format['name'] = String::checkPlain($form_state['values']['date_format_name']);
    $format['pattern'][$this->patternType] = trim($form_state['values']['date_format_pattern']);
    $format['locales'] = !empty($form_state['values']['date_langcode']) ? $form_state['values']['date_langcode'] : array();
    // Formats created in the UI are not locked.
    $format['locked'] = 0;

    system_date_format_save($form_state['values']['date_format_id'], $format);
    if (!empty($form_state['values']['date_format_id'])) {
      drupal_set_message(t('Custom date format updated.'));
    }
    else {
      drupal_set_message(t('Custom date format added.'));
    }

    $form_state['redirect'] = 'admin/config/regional/date-time/formats';
  }

}
