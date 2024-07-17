<?php

declare(strict_types=1);

namespace Drupal\workspaces;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Defines a service for workspaces #lazy_builder callbacks.
 *
 * @internal
 */
final class WorkspacesLazyBuilders implements TrustedCallbackInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly WorkspaceManagerInterface $workspaceManager,
    protected readonly ElementInfoManagerInterface $elementInfo,
  ) {}

  /**
   * Lazy builder callback for rendering the workspace toolbar tab.
   *
   * @return array
   *   A render array.
   */
  public function renderToolbarTab(): array {
    $active_workspace = $this->workspaceManager->getActiveWorkspace();

    $build = [
      '#type' => 'link',
      '#title' => $active_workspace ? $active_workspace->label() : $this->t('Live'),
      '#url' => Url::fromRoute('entity.workspace.collection', [], ['query' => \Drupal::destination()->getAsArray()]),
      '#attributes' => [
        'title' => $this->t('Switch workspace'),
        'class' => [
          'toolbar-item',
          'toolbar-icon',
          'toolbar-icon-workspace',
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
      ],
      '#attached' => [
        'library' => ['workspaces/drupal.workspaces.toolbar'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    // The renderer has already added element defaults by the time the lazy
    // builder is run.
    // @see https://www.drupal.org/project/drupal/issues/2609250
    $build += $this->elementInfo->getInfo('link');
    return $build;
  }

  /**
   * Render callback for the workspace toolbar tab.
   */
  public static function removeTabAttributes(array $element): array {
    unset($element['tab']['#attributes']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['removeTabAttributes', 'renderToolbarTab'];
  }

}
