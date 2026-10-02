<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\Attribute\Route;

/**
 * Returns responses for HTMX Test Fixtures routes.
 */
#[Route(
  name: 'test_htmx.',
  requirements: ['_permission' => 'access content'],
  defaults: ['_title' => 'Htmx Fragment'],
)]
final class HtmxFragmentController extends ControllerBase {

  /**
   * Builds the partial response.
   */
  #[Route(
    path: '/test-htmx/htmx-fragment',
    name: 'htmx_fragment',
    defaults: [
      'target' => '[data-drupal-wrapper-selector="edit-partial-replace"]',
      'swap' => 'outerHTML',
    ]
  )]
  #[Route(
    path: '/test-htmx/htmx-fragment-delete',
    name: 'htmx_fragment_delete',
    defaults: [
      'target' => '[data-drupal-wrapper-selector="edit-partial-replace"]',
      'swap' => 'delete',
    ]
  )]
  public function buildPartial(string $target, string $swap): array {
    return [
      '#type' => 'inline_template',
      '#template' => <<<HTMX_PARTIAL
<hx-partial hx-target="{{ target }}" hx-swap="{{ swap }}">
  {{item}}
</hx-partial>
HTMX_PARTIAL,
      '#context' => [
        'target' => $target,
        'swap' => $swap,
        'item' => $swap === 'delete' ? '' : [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => 'Inserted by htmx!',
        ],
      ],
    ];
  }

  #[Route(
    path: '/test-htmx/htmx-fragment-missing',
    name: 'htmx_fragment_missing',
  )]
  public function buildDeleteInsertPartial(): array {
    return [
      'missing' => $this->buildPartial('div.does-not-exist', 'outerHTML'),
      'insert' => $this->buildPartial('[data-drupal-wrapper-selector="edit-partial-replace"]', 'outerHTML'),
    ];
  }

}
