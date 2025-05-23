<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for HTMX Test Attachments routes.
 */
final class HtmxTestAttachmentsController extends ControllerBase {

  /**
   * Builds the response.
   *
   * @return mixed[]
   *   A render array.
   */
  public function page(): array {
    return self::generateHtmxButton();
  }

  /**
   * Builds the HTMX response.
   *
   * @return mixed[]
   *   A render array.
   */
  public function replace(): array {
    $build['content'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['test_htmx/assets'],
      ],
      '#attributes' => [
        'class' => ['ajax-content'],
      ],
      'example' => ['#markup' => 'Initial Content'],
    ];

    return $build;
  }

  /**
   * Static helper to for reusable render array.
   *
   * @return array
   *   The render array.
   */
  public static function generateHtmxButton(): array {
    $url = Url::fromRoute('test_htmx.attachments.replace');
    $build['replace'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'type' => 'button',
        'name' => 'replace',
        'data-hx-get' => $url->toString(),
        'data-hx-select' => 'div.ajax-content',
        'data-hx-target' => '[data-drupal-htmx-target]',
      ],
      '#value' => 'Click this',
      '#attached' => [
        'library' => [
          'core/drupal.htmx',
        ],
      ],
    ];

    $build['content'] = [
      '#type' => 'container',
      '#attributes' => [
        'data-drupal-htmx-target' => TRUE,
        'class' => ['htmx-test-container'],
      ],
    ];

    return $build;
  }

}
