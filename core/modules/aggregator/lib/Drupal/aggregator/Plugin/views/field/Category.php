<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\views\field\Category.
 */

namespace Drupal\aggregator\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;

/**
 * Defines a simple renderer that allows linking to an aggregator category.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("aggregator_category")
 */
class Category extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['cid'] = 'cid';
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_category'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
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
   * @param string $data
   *   The XSS safe string for the link text.
   * @param object $values
   *   The values retrieved from the database.
   *
   * @return data
   *   Returns string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    $cid = $this->getValue($values, 'cid');
    if (!empty($this->options['link_to_category']) && !empty($cid) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "aggregator/categories/$cid";
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
