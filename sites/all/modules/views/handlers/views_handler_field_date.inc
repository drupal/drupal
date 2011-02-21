<?php
// $Id: views_handler_field_date.inc,v 1.3.4.6 2010/12/23 22:34:37 dereine Exp $
/**
 * A handler to provide proper displays for dates.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_date extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['date_format'] = array('default' => 'small');
    $options['custom_date_format'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['date_format'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#options' => array(
        'small' => t('Short date format') . ' ' . format_date(REQUEST_TIME, 'small'),
        'medium' => t('Medium date format') . ' ' . format_date(REQUEST_TIME, 'medium'),
        'large' => t('Long date format') . ' ' . format_date(REQUEST_TIME, 'large'),
        'custom' => t('Custom'),
        'raw time ago' => t('Time ago'),
        'time ago' => t('Time ago (with "ago" appended)'),
        'raw time span' => t('Time span (future dates start with - )'),
        'time span' => t('Time span (with "ago/hence" appended)'),
      ),
      '#default_value' => isset($this->options['date_format']) ? $this->options['date_format'] : 'small',
    );
    $form['custom_date_format'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom date format'),
      '#description' => t('If "Custom", see <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats. If "Time ago" this is the the number of different units to display, which defaults to two.'),
      '#default_value' => isset($this->options['custom_date_format']) ? $this->options['custom_date_format'] : '',
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('edit-options-date-format' => array('custom', 'raw time ago', 'time ago', 'raw time span', 'time span')),
    );
  }

  function render($values) {
    $value = $values->{$this->field_alias};
    $format = $this->options['date_format'];
    if (in_array($format, array('custom', 'raw time ago', 'time ago', 'raw time span', 'time span'))) {
      $custom_format = $this->options['custom_date_format'];
    }

    if ($value) {
      $time_diff = REQUEST_TIME - $value; // will be positive for a datetime in the past (ago), and negative for a datetime in the future (hence)
      switch ($format) {
        case 'raw time ago':
          return format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time ago':
          return t('%time ago', array('%time' => format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'raw time span':
          return ($time_diff < 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'time span':
          return t(($time_diff < 0 ? '%time hence' : '%time ago'), array('%time' => format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2)));
        case 'custom':
          if ($custom_format == 'r') {
            return format_date($value, $format, $custom_format, null, 'en');
          }
          return format_date($value, $format, $custom_format);
        default:
          return format_date($value, $format);
      }
    }
  }
}
