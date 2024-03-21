<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\views\Attribute\ViewsArea;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("text_custom")]
class TextCustom extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = [
      '#title' => $this->t('Content'),
      '#type' => 'textarea',
      '#description' => $this->t('You may enter data from this view as per the "Available global token replacements" above. You may include the following allowed HTML tags: <code>@tags</code>', [
        '@tags' => '<' . implode('> <', Xss::getAdminTagList()) . '>',
      ]),
      '#default_value' => $this->options['content'],
      '#rows' => 6,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return [
        '#markup' => $this->renderTextarea($this->options['content']),
      ];
    }

    return [];
  }

  /**
   * Render a text area with \Drupal\Component\Utility\Xss::filterAdmin().
   */
  public function renderTextarea($value) {
    if ($value) {
      return $this->sanitizeValue($this->tokenizeValue($value), 'xss_admin');
    }
  }

}
