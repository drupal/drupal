<?php

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\views\Attribute\ViewsArea;

/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("text")]
class Text extends TokenizeAreaPluginBase {

  /**
   * The filter format repository service.
   */
  protected FilterFormatRepositoryInterface $formatRepository;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ?FilterFormatRepositoryInterface $format_repository = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$format_repository) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $format_repository argument is deprecated in drupal:11.4.0 and the $format_repository argument will be required in drupal:12.0.0. See https://www.drupal.org/node/3035368', E_USER_DEPRECATED);
      $format_repository = \Drupal::service(FilterFormatRepositoryInterface::class);
    }
    $this->formatRepository = $format_repository;
  }

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
      '#format' => $this->options['content']['format'] ?? $this->formatRepository->getDefaultFormat()->id(),
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
    $format = $this->options['content']['format'] ?? $this->formatRepository->getDefaultFormat()->id();
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
