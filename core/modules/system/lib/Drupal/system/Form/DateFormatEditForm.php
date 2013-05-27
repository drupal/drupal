<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatEditForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an edit form for date formats.
 */
class DateFormatEditForm extends DateFormatFormBase implements ControllerInterface {

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new date format form.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(ConfigFactory $config_factory) {
    parent::__construct();

    $this->config = $config_factory->get('system.date');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'date_format_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $date_format_id = NULL) {
    form_load_include($form_state, 'admin.inc', 'system');
    $format_info = $this->config->get('formats.' . $date_format_id);
    $pattern = $this->patternType ? $format_info['pattern'][$this->patternType] : '';

    $form['date_format_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Name',
      '#maxlength' => 100,
      '#description' => t('Name of the date format'),
      '#default_value' => empty($format_info['name']) ? '' : $format_info['name']
    );

    $now = t('Displayed as %date', array('%date' => format_date(REQUEST_TIME, $date_format_id)));

    $form['date_format_id'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine-readable name'),
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !empty($date_format_id),
      '#default_value' => $date_format_id,
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
      '#default_value' => $pattern,
      '#field_suffix' => ' <small id="edit-date-format-suffix">' . $now . '</small>',
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
        '#default_value' => empty($format_info['locales']) ? '' : $format_info['locales']
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['update'] = array(
      '#type' => 'submit',
      '#value' => t('Save format'),
    );

    return $form;
  }

}
