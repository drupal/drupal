<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url as CoreUrl;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;

/**
 * Field handler to provide a renderer that turns a URL into a clickable link.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("url")]
class Url extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['display_as_link'] = ['default' => TRUE];

    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['display_as_link'] = [
      '#title' => $this->t('Display as link'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_as_link']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!empty($this->options['display_as_link'])) {
      // @todo Views should expect and store a leading /. See:
      //   https://www.drupal.org/node/2423913
      return Link::fromTextAndUrl($this->sanitizeValue($value), CoreUrl::fromUserInput('/' . $value))->toString();
    }
    else {
      return $this->sanitizeValue($value, 'url');
    }
  }

}
