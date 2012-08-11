<?php

/**
 * @file
 * Definition of views_plugin_row_search_view.
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
 *   title = @Translation("Search"),
 *   no_uid = TRUE
 * )
 */
class View extends RowPluginBase {
  function option_definition() {
    $options = parent::option_definition();

    $options['score'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
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
    return theme($this->theme_functions(),
      array(
        'view' => $this->view,
        'options' => $this->options,
        'row' => $row
      ));
  }
}
