<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Defines a service for workspaces #lazy_builder callbacks.
 *
 * @internal
 */
final class WorkspacesLazyBuilder {

  use RedirectDestinationTrait;
  use StringTranslationTrait;

  public function __construct(
    protected WorkspaceManagerInterface $workspaceManager,
  ) {}

  /**
   * Lazy builder callback for rendering navigation links.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  #[TrustedCallback]
  public function renderNavigationLinks(): array {
    $active_workspace = $this->workspaceManager->getActiveWorkspace();

    $url = Url::fromRoute('entity.workspace.collection', [], ['query' => $this->getDestinationArray()]);
    $url->setOption('attributes', [
      'class' => [
        $active_workspace ? 'toolbar-button--workspaces' : 'toolbar-button--workspaces--live',
        'use-ajax',
      ],
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas_top',
      'data-dialog-options' => Json::encode([
        'height' => 161,
        'classes' => [
          'ui-dialog' => 'workspaces-dialog',
        ],
      ]),
    ]);

    return [
      '#theme' => 'navigation_menu',
      '#title' => $this->t('Workspace'),
      '#items' => [
        [
          'title' => $active_workspace ? $active_workspace->label() : $this->t('Live'),
          'url' => $url,
          'class' => 'workspaces',
          'icon' => [
            'icon_id' => 'workspaces',
          ],
        ],
      ],
      '#attached' => [
        'library' => [
          'navigation/internal.navigation-workspaces',
          'workspaces/drupal.workspaces.off-canvas',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
