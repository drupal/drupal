<?php

/**
 * @file
 * Definition of Views\statistics\Plugin\views\field\AccesslogPath.
 */

namespace Views\statistics\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide simple renderer that turns a URL into a clickable link.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "accesslog_path",
 *   module = "statistics"
 * )
 */
class AccesslogPath extends FieldPluginBase {

  /**
   * Override init function to provide generic option to link to node.
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['display_as_link'])) {
      $this->additional_fields['path'] = 'path';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['display_as_link'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['display_as_link'] = array(
      '#title' => t('Display as link'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_as_link']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

  function render_link($data, $values) {
    if (!empty($this->options['display_as_link'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $this->get_value($values, 'path');
      $this->options['alter']['html'] = TRUE;
    }

    return $data;
  }

}
