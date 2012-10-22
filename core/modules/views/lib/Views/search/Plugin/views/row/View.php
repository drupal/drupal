<?php

/**
 * @file
 * Definition of Views\search\Plugin\views\row\View.
 */

namespace Views\search\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin which performs a node_view on the resulting object.
 *
 * @Plugin(
 *   id = "search_view",
 *   module = "search",
 *   title = @Translation("Search"),
 *   no_uid = TRUE
 * )
 */
class View extends RowPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['score'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['score'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display score'),
      '#default_value' => $this->options['score'],
    );
  }

  /**
   * Override the behavior of the render() function.
   */
  function render($row) {
    return theme($this->themeFunctions(),
      array(
        'view' => $this->view,
        'options' => $this->options,
        'row' => $row
      ));
  }

}
