<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsArea;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("text")]
class Text extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['content'] = [
      'contains' => [
        'value' => ['default' => ''],
        'format' => ['default' => NULL],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = [
      '#title' => $this->t('Content'),
      '#type' => 'text_format',
      '#default_value' => $this->options['content']['value'],
      '#rows' => 6,
      '#format' => $this->options['content']['format'] ?? filter_default_format(),
      '#editor' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery() {
    $content = $this->options['content']['value'];
    // Check for tokens that require a total row count.
    if (str_contains($content, '[view:page-count]') || str_contains($content, '[view:total-rows]')) {
      $this->view->get_total_rows = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $format = $this->options['content']['format'] ?? filter_default_format();
    if (!$empty || !empty($this->options['empty'])) {
      return [
        '#type' => 'processed_text',
        '#text' => $this->tokenizeValue($this->options['content']['value']),
        '#format' => $format,
      ];
    }

    return [];
  }

}
