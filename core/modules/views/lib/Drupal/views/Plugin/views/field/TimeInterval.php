<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\TimeInterval.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * A handler to provide proper displays for time intervals.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("time_interval")
 */
class TimeInterval extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['granularity'] = array('default' => 2);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['granularity'] = array(
      '#type' => 'textfield',
      '#title' => t('Granularity'),
      '#description' => t('How many different units to display in the string.'),
      '#default_value' => $this->options['granularity'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    return format_interval($value, isset($this->options['granularity']) ? $this->options['granularity'] : 2);
  }

}
