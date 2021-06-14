<?php

namespace Drupal\layout_builder\Form;

/**
 * Provides a trait that provides a toggle for the content preview.
 */
trait PreviewToggleTrait {

  /**
   * Builds the content preview toggle input.
   *
   * @return array
   *   The render array for the content preview toggle.
   */
  protected function buildContentPreviewToggle() {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['js-show'],
      ],
      'toggle_content_preview' => [
        '#title' => $this->t('Show content preview'),
        '#type' => 'checkbox',
        '#value' => TRUE,
        '#attributes' => [
          // Set attribute used by local storage to get content preview status.
          'data-content-preview-id' => "Drupal.layout_builder.content_preview.{$this->currentUser()->id()}",
        ],
        '#id' => 'layout-builder-content-preview',
      ],
    ];
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  abstract protected function currentUser();

}
