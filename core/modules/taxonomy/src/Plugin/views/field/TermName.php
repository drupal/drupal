<?php

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * Displays taxonomy term names and allows converting spaces to hyphens.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("term_name")
 */
class TermName extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    if ($this->options['convert_spaces']) {
      foreach ($items as &$item) {
        // Replace spaces with hyphens.
        $name = str_replace(' ', '-', $item['raw']->get('value')->getValue());
        empty($this->options['settings']['link_to_entity']) ?
          $item['rendered']['#context']['value'] = $name :
          $item['rendered']['#title']['#context']['value'] = $name;
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['convert_spaces'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['convert_spaces'] = [
      '#title' => $this->t('Convert spaces in term names to hyphens'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['convert_spaces']),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

}
