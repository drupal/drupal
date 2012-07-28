<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\views\style\List.
 */

namespace Drupal\views\Plugins\views\style;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 */

/**
 * @Plugin(
 *   plugin_id = "html_list",
 *   title = @Translation("HTML List"),
 *   help = @Translation("Displays rows as HTML list."),
 *   theme = "views_view_list",
 *   uses_row_plugin = TRUE,
 *   uses_row_class = TRUE,
 *   uses_options = TRUE,
 *   type = "normal",
 *   help_topic = "style-list"
 * )
 */
class HtmlList extends StylePluginBase {
  /**
   * Set default options
   */
  function option_definition() {
    $options = parent::option_definition();

    $options['type'] = array('default' => 'ul');
    $options['class'] = array('default' => '');
    $options['wrapper_class'] = array('default' => 'item-list');

    return $options;
  }

  /**
   * Render the given style.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => t('List type'),
      '#options' => array('ul' => t('Unordered list'), 'ol' => t('Ordered list')),
      '#default_value' => $this->options['type'],
    );
    $form['wrapper_class'] = array(
      '#title' => t('Wrapper class'),
      '#description' => t('The class to provide on the wrapper, outside the list.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['wrapper_class'],
    );
    $form['class'] = array(
      '#title' => t('List class'),
      '#description' => t('The class to provide on the list element itself.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['class'],
    );
  }
}
