<?php

/**
 * @file
 * Contains \Drupal\dblog\Plugin\views\field\DblogMessage.
 */

namespace Drupal\dblog\Plugin\views\field;

use Drupal\Component\Utility\String;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Provides a field handler that renders a log event with replaced variables.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("dblog_message")
 */
class DblogMessage extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if ($this->options['replace_variables']) {
      $this->additional_fields['variables'] = 'variables';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['replace_variables'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['replace_variables'] = array(
      '#title' => t('Replace variables'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['replace_variables'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if ($this->options['replace_variables']) {
      $variables = unserialize($this->getvalue($values, 'variables'));
      return String::format($value, (array) $variables);
    }
    else {
      return $this->sanitizeValue($value);
    }
  }

}
