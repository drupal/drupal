<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatAddForm.
 */

namespace Drupal\system\Form;

/**
 * Provides an add form for date formats.
 */
class DateFormatAddForm extends DateFormatFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'date_format_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    form_load_include($form_state, 'admin.inc', 'system');
    $form['date_format_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Name',
      '#maxlength' => 100,
      '#description' => t('Name of the date format'),
      '#default_value' => '',
    );

    $form['date_format_id'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine-readable name'),
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => '',
      '#machine_name' => array(
        'exists' => 'system_date_format_exists',
        'source' => array('date_format_name'),
      ),
    );

    if (class_exists('intlDateFormatter')) {
      $description = t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://userguide.icu-project.org/formatparse/datetime'));
    }
    else {
      $description = t('A user-defined date format. See the <a href="@url">PHP manual</a> for available options.', array('@url' => 'http://php.net/manual/function.date.php'));
    }
    $form['date_format_pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('Format string'),
      '#maxlength' => 100,
      '#description' => $description,
      '#default_value' => '',
      '#field_suffix' => ' <small id="edit-date-format-suffix"></small>',
      '#ajax' => array(
        'callback' => 'system_date_time_lookup',
        'event' => 'keyup',
        'progress' => array('type' => 'throbber', 'message' => NULL),
      ),
      '#required' => TRUE,
    );

    $languages = language_list();

    $options = array();
    foreach ($languages as $langcode => $data) {
      $options[$langcode] = $data->name;
    }

    if (!empty($options)) {
      $form['date_langcode'] = array(
        '#title' => t('Select localizations'),
        '#type' => 'select',
        '#options' => $options,
        '#multiple' => TRUE,
        '#default_value' => '',
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['update'] = array(
      '#type' => 'submit',
      '#value' => t('Add format'),
    );

    return $form;
  }

}
