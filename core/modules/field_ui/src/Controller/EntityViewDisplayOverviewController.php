<?php

declare(strict_types=1);

namespace Drupal\field_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Defines a controller to list entity view displays.
 */
class EntityViewDisplayOverviewController extends ControllerBase {

  public function __construct(
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Shows the 'Manage display' overview page.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string|null $bundle
   *   The entity bundle.
   * @param \Drupal\Core\Routing\RouteMatchInterface|null $route_match
   *   The current route match.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview(string $entity_type_id, ?string $bundle = NULL, ?RouteMatchInterface $route_match = NULL): array {
    // Get bundle from route match if not provided.
    if (empty($bundle) && $route_match) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $bundle_entity_type = $entity_type->getBundleEntityType();
      if ($bundle_entity_type) {
        $bundle_entity = $route_match->getParameter($bundle_entity_type);
        $bundle = $bundle_entity ? $bundle_entity->id() : $route_match->getRawParameter($bundle_entity_type);
      }
      else {
        $bundle = $route_match->getRawParameter('bundle') ?: $entity_type_id;
      }
    }
    $build['#attached']['library'][] = 'field_ui/drupal.field_ui_display_overview';
    $manage_view_modes_link = [
      '#type' => 'link',
      '#title' => $this->t('Manage view modes'),
      '#url' => Url::fromRoute('entity.entity_view_mode.collection'),
      '#attributes' => [
        'class' => ['action-link', 'action-link--small', 'action-link--icon-cog'],
      ],
    ];

    // Get all view modes for this entity type.
    $view_modes = $this->entityDisplayRepository->getViewModes($entity_type_id);

    // Sort view modes by label for consistent ordering.
    uasort($view_modes, function ($a, $b) {
      return strnatcasecmp((string) $a['label'], (string) $b['label']);
    });

    // Get all entity view displays for this bundle.
    $storage = $this->entityTypeManager->getStorage('entity_view_display');
    $displays = $storage->loadByProperties([
      'targetEntityType' => $entity_type_id,
      'bundle' => $bundle,
    ]);

    // Build table header.
    $header = [
      'label' => $this->t('View mode'),
      'description' => $this->t('Description'),
      'operations' => $this->t('Operations'),
    ];

    // Index displays by ID for easier lookup.
    $displays_by_id = [];
    foreach ($displays as $display) {
      $displays_by_id[$display->id()] = $display;
    }

    // Get the entity type definition to determine the correct bundle parameter.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $bundle);

    // Separate enabled and disabled display modes.
    $enabled_rows = [];
    $disabled_rows = [];

    // Helper function to build a row.
    $buildRow = function ($display_id, $label, $view_mode_name = NULL) use ($entity_type_id, $route_parameters, $displays_by_id, $view_modes) {
      $display = $displays_by_id[$display_id] ?? NULL;
      // Default view mode is always enabled.
      $is_enabled = ($view_mode_name === 'default') ? TRUE : ($display && $display->status());

      $edit_url = $view_mode_name === 'default'
        ? Url::fromRoute("entity.entity_view_display.{$entity_type_id}.default", $route_parameters)
        : Url::fromRoute("entity.entity_view_display.{$entity_type_id}.view_mode", $route_parameters + [
          'view_mode_name' => $view_mode_name,
        ]);

      $toggle_operation = $is_enabled ? 'disable' : 'enable';
      $toggle_url = Url::fromRoute("entity.entity_view_display.{$entity_type_id}.toggle_status", $route_parameters + [
        'view_mode_name' => $view_mode_name ?: 'default',
        'operation' => $toggle_operation,
      ]);
      // Build operations links.
      $operations_links = [];
      if ($is_enabled) {
        $operations_links['manage'] = [
          'title' => [
            '#markup' => $this->t('Manage <span class="visually-hidden">(@view_mode)</span>', [
              '@view_mode' => $label,
            ]),
          ],
          'url' => $edit_url,
        ];
        // Default view mode cannot be disabled.
        if ($view_mode_name !== 'default') {
          $operations_links['disable'] = [
            'title' => [
              '#markup' => $this->t('Disable <span class="visually-hidden">(@view_mode)</span>', [
                '@view_mode' => $label,
              ]),
            ],
            'url' => $toggle_url,
          ];
        }
      }
      else {
        $operations_links['enable'] = [
          'title' => [
            '#markup' => $this->t('Enable <span class="visually-hidden">(@view_mode)</span>', [
              '@view_mode' => $label,
            ]),
          ],
          'url' => $toggle_url,
        ];
      }

      $description = '';
      if ($view_mode_name !== NULL && $view_mode_name !== '') {
        $description = (string) ($view_modes[$view_mode_name]['description'] ?? '');
      }

      $row = [
        '#attributes' => ['id' => 'display-mode-' . str_replace(['.', '_'], '-', $display_id)],
        'label' => [
          'data' => [
            '#markup' => $label,
          ],
        ],
        'description' => [
          'data' => [
            '#markup' => $description ?: '',
          ],
        ],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $operations_links,
          ],
        ],
      ];

      return ['row' => $row, 'enabled' => $is_enabled];
    };

    // Add default display mode. Default is always enabled and cannot be
    // disabled.
    $default_display_id = $entity_type_id . '.' . $bundle . '.default';
    $default_row_data = $buildRow($default_display_id, $this->t('Default'), 'default');
    // Always add default to enabled rows, even if status is false.
    $enabled_rows['default'] = $default_row_data['row'];

    // Add all other view modes (excluding 'default' which we already added).
    foreach ($view_modes as $view_mode => $view_mode_info) {
      // Skip 'default' as it's already added above.
      if ($view_mode === 'default') {
        continue;
      }

      $display_id = $entity_type_id . '.' . $bundle . '.' . $view_mode;
      $row_data = $buildRow($display_id, $view_mode_info['label'], $view_mode);

      if ($row_data['enabled']) {
        $enabled_rows[$view_mode] = $row_data['row'];
      }
      else {
        $disabled_rows[$view_mode] = $row_data['row'];
      }
    }

    // Build enabled table.
    if (count($enabled_rows) > 0) {
      $build['enabled_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'enabled-display-modes-wrapper'],
      ];
      $build['enabled_wrapper']['heading_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['field-ui-display-overview-heading'],
        ],
      ];
      $build['enabled_wrapper']['heading_wrapper']['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Enabled view modes'),
      ];
      $build['enabled_wrapper']['heading_wrapper']['manage_view_modes'] = $manage_view_modes_link;
      $build['enabled_wrapper']['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#attributes' => [
          'class' => ['display-mode-overview-table'],
        ],
      ];

      foreach ($enabled_rows as $key => $row) {
        $build['enabled_wrapper']['table'][$key] = $row;
      }
    }

    // Build disabled table.
    if (count($disabled_rows) > 0) {
      $build['disabled_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'disabled-display-modes-wrapper'],
      ];
      $build['disabled_wrapper']['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Disabled view modes'),
      ];
      $build['disabled_wrapper']['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#attributes' => [
          'class' => ['display-mode-overview-table'],
        ],
      ];

      foreach ($disabled_rows as $key => $row) {
        $build['disabled_wrapper']['table'][$key] = $row;
      }
    }

    if (count($enabled_rows) === 0 && count($disabled_rows) === 0) {
      $build['empty'] = [
        '#markup' => '<p>' . $this->t('No display modes available.') . '</p>',
      ];
    }

    return $build;
  }

}
