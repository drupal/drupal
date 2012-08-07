<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\AreaPluginBase.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\views\Plugin\views\Plugin;
use Drupal\views\Plugin\views\Handler;

/**
 * @defgroup views_area_handlers Views area handlers
 * @{
 * Handlers to tell Views what can display in header, footer
 * and empty text in a view.
 */

/**
 * Base class for area handlers.
 *
 * @ingroup views_area_handlers
 */

/**
 */
class AreaPluginBase extends Handler {

  /**
   * Overrides Handler::init().
   *
   * Make sure that no result area handlers are set to be shown when the result
   * is empty.
   */
  function init(&$view, &$options) {
    parent::init($view, $options);
    if ($this->handler_type == 'empty') {
      $this->options['empty'] = TRUE;
    }
  }

  /**
   * Get this field's label.
   */
  function label() {
    if (!isset($this->options['label'])) {
      return $this->ui_name();
    }
    return $this->options['label'];
  }

  function option_definition() {
    $options = parent::option_definition();

    $this->definition['field'] = !empty($this->definition['field']) ? $this->definition['field'] : '';
    $label = !empty($this->definition['label']) ? $this->definition['label'] : $this->definition['field'];
    $options['label'] = array('default' => $label, 'translatable' => TRUE);
    $options['empty'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide extra data to the administration form
   */
  function admin_summary() {
    return $this->label();
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => isset($this->options['label']) ? $this->options['label'] : '',
      '#description' => t('The label for this area that will be displayed only administratively.'),
    );

    if ($form_state['type'] != 'empty') {
      $form['empty'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display even if view has no result'),
        '#default_value' => isset($this->options['empty']) ? $this->options['empty'] : 0,
      );
    }
  }

  /**
   * Don't run a query
   */
  function query() { }

  /**
   * Render the area
   */
  function render($empty = FALSE) {
    return '';
  }

  /**
   * Area handlers shouldn't have groupby.
   */
  function use_group_by() {
    return FALSE;
  }
}

/**
 * @}
 */
