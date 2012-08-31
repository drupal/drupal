<?php

/**
 * @file
 * Definition of Views\aggregator\Plugin\views\field\TitleLink.
 */

namespace Views\aggregator\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler that turns an item's title into a clickable link to the original
 * source article.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "aggregator_title_link",
 *   module = "aggregator"
 * )
 */
class TitleLink extends FieldPluginBase {

  public function construct() {
    parent::construct();
    $this->additional_fields['link'] = 'link';
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
    return $this->render_link($this->sanitize_value($value), $values);
  }

  function render_link($data, $values) {
    $link = $this->get_value($values, 'link');
    if (!empty($this->options['display_as_link'])) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $link;
      $this->options['alter']['html'] = TRUE;
    }

    return $data;
  }

}
