<?php

namespace Drupal\views;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
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
   * The formatter plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $formatterPluginManager;

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
   * @param \Drupal\Component\Plugin\PluginManagerInterface $formatter_plugin_manager
   *   The formatter plugin manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    TypedConfigManagerInterface $typed_config_manager,
    ViewsData $views_data,
    PluginManagerInterface $formatter_plugin_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->typedConfigManager = $typed_config_manager;
    $this->viewsData = $views_data;
    $this->formatterPluginManager = $formatter_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.typed'),
      $container->get('views.views_data'),
      $container->get('plugin.manager.field.formatter')
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
      if ($this->processEntityArgumentUpdate($view)) {
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
    $handler_types = [
      'field' => 'fields',
      'argument' => 'arguments',
      'sort' => 'sorts',
      'relationship' => 'relationships',
      'filter' => 'filters',
      'pager' => 'pager',
    ];

    $compound_display_handlers = [
      'pager',
    ];

    foreach ($displays as $display_id => &$display) {
      foreach ($handler_types as $handler_type => $handler_type_lookup) {
        if (!empty($display['display_options'][$handler_type_lookup])) {
          if (in_array($handler_type_lookup, $compound_display_handlers)) {
            if ($handler_processor($display['display_options'][$handler_type_lookup], $handler_type, NULL, $display_id)) {
              $changed = TRUE;
              if ($return_on_changed) {
                return $changed;
              }
            }
            continue;
          }
          foreach ($display['display_options'][$handler_type_lookup] as $key => &$handler) {
            if (is_array($handler) && $handler_processor($handler, $handler_type, $key, $display_id)) {
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
   * Checks if 'numeric' arguments should be converted to 'entity_target_id'.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   TRUE if the view has any arguments that reference an entity reference
   *   that need to be converted from 'numeric' to 'entity_target_id'.
   */
  public function needsEntityArgumentUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processEntityArgumentUpdate($view);
    });
  }

  /**
   * Processes arguments and convert 'numeric' to 'entity_target_id' if needed.
   *
   * Note that since this update will trigger deprecations if called by
   * views_view_presave(), we cannot rely on the usual handler-specific checking
   * and processing. That would still hit views_view_presave(), even when
   * invoked from post_update. We must directly update the view here, so that
   * it's already correct by the time views_view_presave() sees it.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View being updated.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function processEntityArgumentUpdate(ViewEntityInterface $view): bool {
    $changed = FALSE;

    $displays = $view->get('display');
    foreach ($displays as &$display) {
      if (isset($display['display_options']['arguments'])) {
        foreach ($display['display_options']['arguments'] as $argument_id => $argument) {
          $plugin_id = $argument['plugin_id'] ?? '';
          if ($plugin_id === 'numeric') {
            $argument_table_data = $this->viewsData->get($argument['table']);
            $argument_definition = $argument_table_data[$argument['field']]['argument'] ?? [];
            if (isset($argument_definition['id']) && $argument_definition['id'] === 'entity_target_id') {
              $argument['plugin_id'] = 'entity_target_id';
              $argument['target_entity_type_id'] = $argument_definition['target_entity_type_id'];
              $display['display_options']['arguments'][$argument_id] = $argument;
              $changed = TRUE;
            }
          }
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    $deprecations_triggered = &$this->triggeredDeprecations['2640994'][$view->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The update to convert "numeric" arguments to "entity_target_id" for entity reference fields for view "%s" is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3441945', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

}
