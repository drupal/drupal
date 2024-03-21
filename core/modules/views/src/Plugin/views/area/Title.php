<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsArea;

/**
 * Views area title override handler.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("title")]
class Title extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Overridden title'),
      '#default_value' => $this->options['title'],
      '#description' => $this->t('Override the title of this view when it is empty. The available global tokens below can be used here.'),
    ];

    // Don't use the AreaPluginBase tokenForm method, we don't want row tokens.
    $this->globalTokenForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(array $results) {
    parent::preRender($results);

    // If a title is provided, process it.
    if (!empty($this->options['title'])) {
      $value = $this->globalTokenReplace($this->options['title']);
      $this->view->setTitle($this->sanitizeValue($value, 'xss_admin'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // Do nothing for this handler by returning an empty render array.
    return [];
  }

}
