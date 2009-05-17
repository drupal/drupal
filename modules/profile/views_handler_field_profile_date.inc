<?php

/**
 * @file
 * Definition of views_handler_field_profile_date.
 */

/**
 * Field handler display a profile date
 *
 * The dates are stored serialized, which makes them mostly useless from
 * SQL. About all we can do is unserialize and display them.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_profile_date extends views_handler_field_date {
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    // we can't do "time ago" so remove it from the form.
    unset($form['date_format']['#options']['time ago']);
  }

  /**
   * Display a profile field of type 'date'
   */
  function render($values) {
    $value = $this->get_value($values);
    if (!$value) {
      return;
    }
    $value = unserialize($value);
    $format = $this->options['date_format'];
    switch ($format) {
      case 'custom':
        $format = $this->options['custom_date_format'];
        break;
      case 'small':
        $format = variable_get('date_format_short', 'm/d/Y - H:i');
        break;
      case 'medium':
        $format = variable_get('date_format_medium', 'D, m/d/Y - H:i');
        break;
      case 'large':
        $format = variable_get('date_format_long', 'l, F j, Y - H:i');
        break;
    }

    // Note: Avoid PHP's date() because it does not handle dates before
    // 1970 on Windows. This would make the date field useless for e.g.
    // birthdays.

    // But we *can* deal with non-year stuff:
    $date = gmmktime(0, 0, 0, $value['month'], $value['day'], $value['year']);
    $replace = array(
      // day
      'd' => sprintf('%02d', $value['day']),
      'D' => NULL,
      'l' => NULL,
      'N' => NULL,
      'S' => date('S', $date),
      'w' => NULL,
      'j' => $value['day'],
      // month
      'F' => date('F', $date),
      'm' => sprintf('%02d', $value['month']),
      'M' => date('M', $date),
      'n' => date('n', $date),

      'Y' => $value['year'],
      'y' => substr($value['year'], 2, 2),

      // kill time stuff
      'a' => NULL,
      'A' => NULL,
      'g' => NULL,
      'G' => NULL,
      'h' => NULL,
      'H' => NULL,
      'i' => NULL,
      's' => NULL,
      ':' => NULL,
      'T' => NULL,
      ' - ' => NULL,
      ':' => NULL,
    );

    return strtr($format, $replace);
  }
}
