<?php

namespace Drupal\field_ui\Plugin\Derivative;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class FieldUiLocalTask extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Creates a FieldUiLocalTask object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        $this->derivatives["overview_$entity_type_id"] = [
          'route_name' => "entity.$entity_type_id.field_ui_fields",
          'weight' => 1,
          'title' => $this->t('Manage fields'),
          'base_route' => "entity.$entity_type_id.field_ui_fields",
        ];

        // 'Manage form display' tab.
        $this->derivatives["form_display_overview_$entity_type_id"] = [
          'route_name' => "entity.entity_form_display.$entity_type_id.default",
          'weight' => 2,
          'title' => $this->t('Manage form display'),
          'base_route' => "entity.$entity_type_id.field_ui_fields",
        ];

        // 'Manage display' tab.
        $this->derivatives["display_overview_$entity_type_id"] = [
          'route_name' => "entity.entity_view_display.$entity_type_id.default",
          'weight' => 3,
          'title' => $this->t('Manage display'),
          'base_route' => "entity.$entity_type_id.field_ui_fields",
        ];

        // Field edit tab.
        $this->derivatives["field_edit_$entity_type_id"] = [
          'route_name' => "entity.field_config.{$entity_type_id}_field_edit_form",
          'title' => $this->t('Edit'),
          'base_route' => "entity.field_config.{$entity_type_id}_field_edit_form",
        ];

        // Field settings tab.
        $this->derivatives["field_storage_$entity_type_id"] = [
          'route_name' => "entity.field_config.{$entity_type_id}_storage_edit_form",
          'title' => $this->t('Field settings'),
          'base_route' => "entity.field_config.{$entity_type_id}_field_edit_form",
        ];

        // View and form modes secondary tabs.
        // The same base $path for the menu item (with a placeholder) can be
        // used for all bundles of a given entity type; but depending on
        // administrator settings, each bundle has a different set of view
        // modes available for customization. So we define menu items for all
        // view modes, and use a route requirement to determine which ones are
        // actually visible for a given bundle.
        $this->derivatives['field_form_display_default_' . $entity_type_id] = [
          'title' => 'Default',
          'route_name' => "entity.entity_form_display.$entity_type_id.default",
          'parent_id' => "field_ui.fields:form_display_overview_$entity_type_id",
          'weight' => -1,
        ];
        $this->derivatives['field_display_default_' . $entity_type_id] = [
          'title' => 'Default',
          'route_name' => "entity.entity_view_display.$entity_type_id.default",
          'parent_id' => "field_ui.fields:display_overview_$entity_type_id",
          'weight' => -1,
        ];

        // One local task for each form mode.
        $weight = 0;
        foreach ($this->entityDisplayRepository->getFormModes($entity_type_id) as $form_mode => $form_mode_info) {
          $this->derivatives['field_form_display_' . $form_mode . '_' . $entity_type_id] = [
            'title' => $form_mode_info['label'],
            'route_name' => "entity.entity_form_display.$entity_type_id.form_mode",
            'route_parameters' => [
              'form_mode_name' => $form_mode,
            ],
            'parent_id' => "field_ui.fields:form_display_overview_$entity_type_id",
            'weight' => $weight++,
            'cache_tags' => $this->entityTypeManager->getDefinition('entity_form_display')->getListCacheTags(),
          ];
        }

        // One local task for each view mode.
        $weight = 0;
        foreach ($this->entityDisplayRepository->getViewModes($entity_type_id) as $view_mode => $form_mode_info) {
          $this->derivatives['field_display_' . $view_mode . '_' . $entity_type_id] = [
            'title' => $form_mode_info['label'],
            'route_name' => "entity.entity_view_display.$entity_type_id.view_mode",
            'route_parameters' => [
              'view_mode_name' => $view_mode,
            ],
            'parent_id' => "field_ui.fields:display_overview_$entity_type_id",
            'weight' => $weight++,
            'cache_tags' => $this->entityTypeManager->getDefinition('entity_view_display')->getListCacheTags(),
          ];
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Alters the base_route definition for field_ui local tasks.
   *
   * @param array $local_tasks
   *   An array of local tasks plugin definitions, keyed by plugin ID.
   */
  public function alterLocalTasks(&$local_tasks) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        $local_tasks["field_ui.fields:overview_$entity_type_id"]['base_route'] = $route_name;
        $local_tasks["field_ui.fields:form_display_overview_$entity_type_id"]['base_route'] = $route_name;
        $local_tasks["field_ui.fields:display_overview_$entity_type_id"]['base_route'] = $route_name;
        $local_tasks["field_ui.fields:field_form_display_default_$entity_type_id"]['base_route'] = $route_name;
        $local_tasks["field_ui.fields:field_display_default_$entity_type_id"]['base_route'] = $route_name;

        foreach ($this->entityDisplayRepository->getFormModes($entity_type_id) as $form_mode => $form_mode_info) {
          $local_tasks['field_ui.fields:field_form_display_' . $form_mode . '_' . $entity_type_id]['base_route'] = $route_name;
        }

        foreach ($this->entityDisplayRepository->getViewModes($entity_type_id) as $view_mode => $form_mode_info) {
          $local_tasks['field_ui.fields:field_display_' . $view_mode . '_' . $entity_type_id]['base_route'] = $route_name;
        }
      }
    }
  }

}
