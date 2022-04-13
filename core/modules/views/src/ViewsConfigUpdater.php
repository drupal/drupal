<?php

namespace Drupal\views;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 *   This class is only meant to fix outdated views configuration and its
 *   methods should not be invoked directly. It will be removed once all the
 *   deprecated methods have been removed.
 */
class ViewsConfigUpdater implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * An array of helper data for the multivalue base field update.
   *
   * @var array
   */
  protected $multivalueBaseFieldsUpdateTableInfo;

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  protected $deprecationsEnabled = TRUE;

  /**
   * Stores which deprecations were triggered.
   *
   * @var bool
   */
  protected $triggeredDeprecations = [];

  /**
   * ViewsConfigUpdater constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    TypedConfigManagerInterface $typed_config_manager,
    ViewsData $views_data
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->typedConfigManager = $typed_config_manager;
    $this->viewsData = $views_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.typed'),
      $container->get('views.views_data')
    );
  }

  /**
   * Sets the deprecations enabling status.
   *
   * @param bool $enabled
   *   Whether deprecations should be enabled.
   */
  public function setDeprecationsEnabled($enabled) {
    $this->deprecationsEnabled = $enabled;
  }

  /**
   * Performs all required updates.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function updateAll(ViewEntityInterface $view) {
    return $this->processDisplayHandlers($view, FALSE, function (&$handler, $handler_type, $key, $display_id) use ($view) {
      $changed = FALSE;
      if ($this->processEntityLinkUrlHandler($handler, $handler_type, $view)) {
        $changed = TRUE;
      }
      if ($this->processOperatorDefaultsHandler($handler, $handler_type, $view)) {
        $changed = TRUE;
      }
      if ($this->processMultivalueBaseFieldHandler($handler, $handler_type, $key, $display_id, $view)) {
        $changed = TRUE;
      }
      if ($this->processSortFieldIdentifierUpdateHandler($handler, $handler_type)) {
        $changed = TRUE;
      }
      if ($this->processImageLazyLoadFieldHandler($handler, $handler_type, $view)) {
        $changed = TRUE;
      }
      return $changed;
    });
  }

  /**
   * Processes all display handlers.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   * @param bool $return_on_changed
   *   Whether processing should stop after a change is detected.
   * @param callable $handler_processor
   *   A callback performing the actual update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  protected function processDisplayHandlers(ViewEntityInterface $view, $return_on_changed, callable $handler_processor) {
    $changed = FALSE;
    $displays = $view->get('display');
    $handler_types = ['field', 'argument', 'sort', 'relationship', 'filter'];

    foreach ($displays as $display_id => &$display) {
      foreach ($handler_types as $handler_type) {
        $handler_type_plural = $handler_type . 's';
        if (!empty($display['display_options'][$handler_type_plural])) {
          foreach ($display['display_options'][$handler_type_plural] as $key => &$handler) {
            if ($handler_processor($handler, $handler_type, $key, $display_id)) {
              $changed = TRUE;
              if ($return_on_changed) {
                return $changed;
              }
            }
          }
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    return $changed;
  }

  /**
   * Add additional settings to the entity link field.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   *
   * @deprecated in drupal:9.0.0 and is removed from drupal:10.0.0.
   *   Module-provided Views configuration should be updated to accommodate the
   *   changes described below.
   *
   * @see https://www.drupal.org/node/2857891
   */
  public function needsEntityLinkUrlUpdate(ViewEntityInterface $view) {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processEntityLinkUrlHandler($handler, $handler_type, $view);
    });
  }

  /**
   * Processes entity link URL fields.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View being updated.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processEntityLinkUrlHandler(array &$handler, $handler_type, ViewEntityInterface $view) {
    $changed = FALSE;

    if ($handler_type === 'field') {
      if (isset($handler['plugin_id']) && $handler['plugin_id'] === 'entity_link') {
        // Add any missing settings for entity_link.
        if (!isset($handler['output_url_as_text'])) {
          $handler['output_url_as_text'] = FALSE;
          $changed = TRUE;
        }
        if (!isset($handler['absolute'])) {
          $handler['absolute'] = FALSE;
          $changed = TRUE;
        }
      }
      elseif (isset($handler['plugin_id']) && $handler['plugin_id'] === 'node_path') {
        // Convert the use of node_path to entity_link.
        $handler['plugin_id'] = 'entity_link';
        $handler['field'] = 'view_node';
        $handler['output_url_as_text'] = TRUE;
        $changed = TRUE;
      }
    }

    $deprecations_triggered = &$this->triggeredDeprecations['2857891'][$view->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The entity link url update for the "%s" view is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Module-provided Views configuration should be updated to accommodate the changes described at https://www.drupal.org/node/2857891.', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Add additional settings to the entity link field.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   *
   * @deprecated in drupal:9.0.0 and is removed from drupal:10.0.0.
   *   Module-provided Views configuration should be updated to accommodate the
   *   changes described below.
   *
   * @see https://www.drupal.org/node/2869168
   */
  public function needsOperatorDefaultsUpdate(ViewEntityInterface $view) {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processOperatorDefaultsHandler($handler, $handler_type, $view);
    });
  }

  /**
   * Processes operator defaults.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View being updated.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processOperatorDefaultsHandler(array &$handler, $handler_type, ViewEntityInterface $view) {
    $changed = FALSE;

    if ($handler_type === 'filter') {
      if (!isset($handler['expose']['operator_limit_selection'])) {
        $handler['expose']['operator_limit_selection'] = FALSE;
        $changed = TRUE;
      }
      if (!isset($handler['expose']['operator_list'])) {
        $handler['expose']['operator_list'] = [];
        $changed = TRUE;
      }
    }

    $deprecations_triggered = &$this->triggeredDeprecations['2869168'][$view->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The operator defaults update for the "%s" view is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Module-provided Views configuration should be updated to accommodate the changes described at https://www.drupal.org/node/2869168.', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Update field names for multi-value base fields.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   *
   * @deprecated in drupal:9.0.0 and is removed from drupal:10.0.0.
   *   Module-provided Views configuration should be updated to accommodate the
   *   changes described below.
   *
   * @see https://www.drupal.org/node/2900684
   */
  public function needsMultivalueBaseFieldUpdate(ViewEntityInterface $view) {
    if ($this->getMultivalueBaseFieldUpdateTableInfo()) {
      return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type, $key, $display_id) use ($view) {
        return $this->processMultivalueBaseFieldHandler($handler, $handler_type, $key, $display_id, $view);
      });
    }
    return FALSE;
  }

  /**
   * Returns the multivalue base fields update table info.
   *
   * @return array
   *   An array of multivalue base field info.
   */
  protected function getMultivalueBaseFieldUpdateTableInfo() {
    $table_info = &$this->multivalueBaseFieldsUpdateTableInfo;

    if (!isset($table_info)) {
      $table_info = [];

      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
        if ($entity_type->hasHandlerClass('views_data') && $entity_type->entityClassImplements(FieldableEntityInterface::class)) {
          $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

          $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
          $table_mapping = $entity_storage->getTableMapping($base_field_definitions);
          if (!$table_mapping instanceof DefaultTableMapping) {
            continue;
          }

          foreach ($base_field_definitions as $field_name => $base_field_definition) {
            $base_field_storage_definition = $base_field_definition->getFieldStorageDefinition();

            // Skip single value and custom storage base fields.
            if (!$base_field_storage_definition->isMultiple() || $base_field_storage_definition->hasCustomStorage()) {
              continue;
            }

            // Get the actual table, as well as the column for the main property
            // name, so we can perform an update on the views in
            // ::updateFieldNamesForMultivalueBaseFields().
            $table_name = $table_mapping->getFieldTableName($field_name);
            $main_property_name = $base_field_storage_definition->getMainPropertyName();

            $table_info[$table_name][$field_name] = $table_mapping->getFieldColumnName($base_field_storage_definition, $main_property_name);
          }
        }
      }
    }

    return $table_info;
  }

  /**
   * Processes handlers affected by the multivalue base field update.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   * @param string $key
   *   The handler key.
   * @param string $display_id
   *   The handler display ID.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view being updated.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processMultivalueBaseFieldHandler(array &$handler, $handler_type, $key, $display_id, ViewEntityInterface $view) {
    $changed = FALSE;

    // If there are no multivalue base fields we have nothing to do.
    $table_info = $this->getMultivalueBaseFieldUpdateTableInfo();
    if (!$table_info) {
      return $changed;
    }

    // Only if the wrong field name is set do we process the field. It
    // could already be using the correct field. Like "user__roles" vs
    // "roles_target_id".
    if (isset($handler['table']) && isset($table_info[$handler['table']]) && isset($table_info[$handler['table']][$handler['field']])) {
      $changed = TRUE;
      $original_field_name = $handler['field'];
      $handler['field'] = $table_info[$handler['table']][$original_field_name];
      $handler['plugin_id'] = $this->viewsData->get($handler['table'])[$table_info[$handler['table']][$original_field_name]][$handler_type]['id'];

      // Retrieve type data information about the handler to clean it up
      // reliably. We need to manually create a typed view rather than
      // instantiating the current one, as the schema will be affected by the
      // updated values.
      $id = 'views.view.' . $view->id();
      $path_to_handler = "display.$display_id.display_options.{$handler_type}s.$key";
      $view_config = $view->toArray();
      $keys = explode('.', $path_to_handler);
      NestedArray::setValue($view_config, $keys, $handler);
      /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_view */
      $typed_view = $this->typedConfigManager->createFromNameAndData($id, $view_config);
      /** @var \Drupal\Core\Config\Schema\ArrayElement $typed_handler */
      $typed_handler = $typed_view->get($path_to_handler);

      // Filter values we want to convert from a string to an array.
      if ($handler_type === 'filter' && $typed_handler->get('value') instanceof ArrayElement && is_string($handler['value'])) {
        // An empty string cast to an array is an array with one element.
        if ($handler['value'] === '') {
          $handler['value'] = [];
        }
        else {
          $handler['value'] = (array) $handler['value'];
        }
        $handler['operator'] = $this->mapOperatorFromSingleToMultiple($handler['operator']);
      }

      // For all the other fields we try to determine the fields using config
      // schema and remove everything not being defined in the new handler.
      foreach (array_keys($handler) as $handler_key) {
        if (!isset($typed_handler->getDataDefinition()['mapping'][$handler_key])) {
          unset($handler[$handler_key]);
        }
      }
    }

    $deprecations_triggered = &$this->triggeredDeprecations['2900684'][$view->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The multivalue base field update for the "%s" view is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Module-provided Views configuration should be updated to accommodate the changes described at https://www.drupal.org/node/2900684.', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Maps a single operator to a multiple one, if possible.
   *
   * @param string $single_operator
   *   A single operator.
   *
   * @return string
   *   A multiple operator or the original one if no mapping was available.
   */
  protected function mapOperatorFromSingleToMultiple($single_operator) {
    switch ($single_operator) {
      case '=':
        return 'or';

      case '!=':
        return 'not';

      default:
        return $single_operator;
    }
  }

  /**
   * Updates the sort handlers by adding default sort field identifiers.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function needsSortFieldIdentifierUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (array &$handler, string $handler_type): bool {
      return $this->processSortFieldIdentifierUpdateHandler($handler, $handler_type);
    });
  }

  /**
   * Add lazy load options to all image type field configurations.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function needsImageLazyLoadFieldUpdate(ViewEntityInterface $view) {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processImageLazyLoadFieldHandler($handler, $handler_type, $view);
    });
  }

  /**
   * Processes image type fields.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View being updated.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processImageLazyLoadFieldHandler(array &$handler, string $handler_type, ViewEntityInterface $view) {
    $changed = FALSE;

    // Add any missing settings for lazy loading.
    if (($handler_type === 'field')
      && isset($handler['plugin_id'], $handler['type'])
      && $handler['plugin_id'] === 'field'
      && $handler['type'] === 'image'
      && !isset($handler['settings']['image_loading'])) {
      $handler['settings']['image_loading'] = ['attribute' => 'lazy'];
      $changed = TRUE;
    }

    return $changed;
  }

  /**
   * Processes sort handlers by adding the sort identifier.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processSortFieldIdentifierUpdateHandler(array &$handler, string $handler_type): bool {
    if ($handler_type === 'sort' && !isset($handler['expose']['field_identifier'])) {
      $handler['expose']['field_identifier'] = $handler['id'];
      return TRUE;
    }
    return FALSE;
  }

}
