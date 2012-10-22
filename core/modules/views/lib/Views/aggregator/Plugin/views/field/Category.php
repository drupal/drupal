<?php

/**
 * @file
 * Definition of Views\aggregator\Plugin\views\field\Category.
 */

namespace Views\aggregator\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide simple renderer that allows linking to aggregator
 * category.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "aggregator_category",
 *   module = "aggregator"
 * )
 */
class Category extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['cid'] = 'cid';
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_category'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to category option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_category'] = array(
      '#title' => t('Link this field to its aggregator category page'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_category']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Render whatever the data is as a link to the category.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    $cid = $this->get_value($values, 'cid');
    if (!empty($this->options['link_to_category']) && !empty($cid) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "aggregator/category/$cid";
    }
    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
