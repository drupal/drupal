<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Controller;

use Drupal\Core\Controller\ControllerBase;
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
  public function empty(): array {
    return self::generateHtmxButton();
  }

  /**
   * Builds the response with a given swap.
   *
   * @return mixed[]
   *   A render array.
   */
  public function swap(string $swap): array {
    $swap = match ($swap) {
      'inner-html' => 'innerHTML',
      'outer-html' => 'outerHTML',
      'text-content' => 'textContent',
      'inner-morph' => 'innerMorph',
      'outer-morph' => 'outerMorph',
      'outer-sync' => 'outerSync',
      default => $swap,
    };
    return self::generateHtmxButton(swap: $swap);
  }

  /**
   * Builds a response with the wrapper format parameter on the request.
   *
   * @return mixed[]
   *   A render array.
   */
  public function withWrapperFormat(): array {
    return self::generateHtmxButton(swap: '', useWrapperFormat: TRUE);
  }

  /**
   * Tests body targeting and swapping.
   *
   * @return mixed[]
   *   A render array.
   */
  public function selectBody(): array {
    $build = [
      '#title' => $this->t('Boosted body'),
      '#type' => 'link',
      '#url' => Url::fromRoute('test_htmx.attachments.replace'),
      '#attributes' => [
        'class' => ['htmx-test-link'],
      ],
    ];
    (new Htmx())->boost(TRUE)->applyTo($build);
    return $build;
  }

  /**
   * Set up a delete swap test.
   *
   * @return mixed[]
   *   A render array.
   */
  public function delete(): array {
    $url = Url::fromRoute('test_htmx.attachments.replace');
    // Start with the "replace" content.
    $build = $this->replace();
    // Add a delete button.
    $build['delete'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#attributes' => [
        'type' => 'button',
        'name' => 'delete',
      ],
      '#value' => 'Delete',
    ];
    (new Htmx())
      ->get($url)
      ->swap('delete')
      ->target('div.ajax-content')
      ->applyTo($build['delete']);
    return $build;
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

    $request = \Drupal::request();
    $format = $request->query->get('_wrapper_format');
    if ($format === 'drupal_htmx') {
      // The query parameter was set.
      $build['content']['#attributes']['class'][] = 'htmx-test-flag';
    }
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
    $url = Url::fromRoute('test_htmx.attachments.replace');
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
