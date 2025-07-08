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
   * Builds a response with a `beforebegin` swap.
   *
   * @return mixed[]
   *   A render array.
   */
  public function before(): array {
    return self::generateHtmxButton('beforebegin');
  }

  /**
   * Builds a response with an `afterend` swap..
   *
   * @return mixed[]
   *   A render array.
   */
  public function after(): array {
    return self::generateHtmxButton('afterend');
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
   * We need a static callback that ignores callback parameters.
   *
   * @return array
   *   The render array.
   */
  public static function replaceWithAjax(): array {
    return static::generateHtmxButton();
  }

  /**
   * Static helper to for reusable render array.
   *
   * @return array
   *   The render array.
   */
  public static function generateHtmxButton(string $swap = ''): array {
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
    if ($swap !== '') {
      $build['replace']['#attributes']['data-hx-swap'] = $swap;
    }

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
