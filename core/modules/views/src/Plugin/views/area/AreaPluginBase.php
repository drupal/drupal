<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\area\AreaPluginBase.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
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
   * The type of this area handler, i.e. 'header', 'footer', or 'empty'.
   *
   * @var string
   */
  public $areaType;

  /**
   * Overrides Drupal\views\Plugin\views\HandlerBase::init().
   *
   * Make sure that no result area handlers are set to be shown when the result
   * is empty.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if ($this->areaType == 'empty') {
      $this->options['empty'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $this->definition['field'] = !empty($this->definition['field']) ? $this->definition['field'] : '';
    $label = !empty($this->definition['label']) ? $this->definition['label'] : $this->definition['field'];
    $options['admin_label']['default'] = $label;
    $options['empty'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->adminLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($form_state['type'] != 'empty') {
      $form['empty'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display even if view has no result'),
        '#default_value' => isset($this->options['empty']) ? $this->options['empty'] : 0,
      );
    }
  }

  /**
   * Performs any operations needed before full rendering.
   *
   * @param array $results
   *  The results of the view.
   */
  public function preRender(array $results) {
  }

  /**
   * Render the area.
   *
   * @param bool $empty
   *   (optional) Indicator if view result is empty or not. Defaults to FALSE.
   *
   * @return array
   *   In any case we need a valid Drupal render array to return.
   */
  public abstract function render($empty = FALSE);

  /**
   * Does that area have nothing to show.
   *
   * This method should be overridden by more complex handlers where the output
   * is not static and maybe itself be empty if it's rendered.
   *
   * @return bool
   *   Return TRUE if the area is empty, else FALSE.
   */
  public function isEmpty() {
    return empty($this->options['empty']);
  }

}

/**
 * @}
 */
