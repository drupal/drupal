<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Url.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as CoreUrl;
use Drupal\views\ResultRow;

/**
 * Field handler to provide simple renderer that turns a URL into a clickable link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("url")
 */
class Url extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['display_as_link'] = array('default' => TRUE);

    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['display_as_link'] = array(
      '#title' => $this->t('Display as link'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_as_link']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!empty($this->options['display_as_link'])) {
      return \Drupal::l($this->sanitizeValue($value), CoreUrl::fromUri('user-path:/' . $value), array('html' => TRUE));
    }
    else {
      return $this->sanitizeValue($value, 'url');
    }
  }

}
