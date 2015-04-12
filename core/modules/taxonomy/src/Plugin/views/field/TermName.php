<?php
/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\views\field\TermName.
 */

namespace Drupal\taxonomy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Field;
use Drupal\views\ResultRow;

/**
 * Displays taxonomy term names and allows converting spaces to hyphens.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("term_name")
 */
class TermName extends Field {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    if ($this->options['convert_spaces']) {
      foreach ($items as &$item) {
        // Replace spaces with hyphens.
        $name = $item['raw']->get('value')->getValue();
        $item['rendered']['#markup'] = str_replace(' ', '-', $name);
      }
    }
    return $items;
  }


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['convert_spaces'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['convert_spaces'] = array(
      '#title' => $this->t('Convert spaces in term names to hyphens'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['convert_spaces']),
    );

    parent::buildOptionsForm($form, $form_state);
  }

}
