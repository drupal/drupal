<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Htmx\Htmx;
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
   * Builds a response with an `afterend` swap.
   *
   * @return mixed[]
   *   A render array.
   */
  public function after(): array {
    return self::generateHtmxButton('afterend');
  }

  /**
   * Builds a response with an the wrapper format parameter on the request.
   *
   * @return mixed[]
   *   A render array.
   */
  public function withWrapperFormat(): array {
    return self::generateHtmxButton('', TRUE);
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
  public static function generateHtmxButton(string $swap = '', bool $useWrapperFormat = FALSE): array {
    $options = [];
    if ($useWrapperFormat) {
      $options = [
        'query' => [
          MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_htmx',
        ],
      ];
    }
    $url = Url::fromRoute('test_htmx.attachments.replace', [], $options);
    $build['replace'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'type' => 'button',
        'name' => 'replace',
      ],
      '#value' => 'Click this',
    ];
    $replace_htmx = (new Htmx())
      ->get($url)
      ->onlyMainContent($useWrapperFormat)
      ->select('div.ajax-content')
      ->target('[data-drupal-htmx-target]');
    if ($swap !== '') {
      $replace_htmx->swap($swap);
    }
    $replace_htmx->applyTo($build['replace']);

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
