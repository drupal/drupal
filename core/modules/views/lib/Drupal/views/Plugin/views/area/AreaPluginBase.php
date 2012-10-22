<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\AreaPluginBase.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Plugin\views\HandlerBase;

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
abstract class AreaPluginBase extends HandlerBase {

  /**
   * Overrides Drupal\views\Plugin\views\HandlerBase::init().
   *
   * Make sure that no result area handlers are set to be shown when the result
   * is empty.
   */
  public function init(ViewExecutable $view, &$options) {
    $this->setOptionDefaults($this->options, $this->defineOptions());
    parent::init($view, $options);

    if (isset($this->handler_type) && ($this->handler_type == 'empty')) {
      $this->options['empty'] = TRUE;
    }
  }

  /**
   * Get this area's label.
   */
  public function label() {
    if (!isset($this->options['label'])) {
      return $this->adminLabel();
    }
    return $this->options['label'];
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $this->definition['field'] = !empty($this->definition['field']) ? $this->definition['field'] : '';
    $label = !empty($this->definition['label']) ? $this->definition['label'] : $this->definition['field'];
    $options['label'] = array('default' => $label, 'translatable' => TRUE);
    $options['empty'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide extra data to the administration form
   */
  public function adminSummary() {
    return $this->label();
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
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
  public function query() { }

  /**
   * Render the area
   */
  function render($empty = FALSE) {
    return '';
  }

  /**
   * Area handlers shouldn't have groupby.
   */
  public function usesGroupBy() {
    return FALSE;
  }

}

/**
 * @}
 */
