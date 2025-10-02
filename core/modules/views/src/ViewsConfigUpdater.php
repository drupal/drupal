<?php

namespace Drupal\views;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 */
class ViewsConfigUpdater {

  /**
   * Flag determining whether deprecations should be triggered.
   */
  protected bool $deprecationsEnabled = TRUE;

  /**
   * Stores which deprecations were triggered.
   */
  protected array $triggeredDeprecations = [];

  /**
   * ViewsConfigUpdater constructor.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly TypedConfigManagerInterface $typedConfigManager,
    private readonly ViewsData $viewsData,
    #[Autowire(service: 'plugin.manager.field.formatter')]
    private readonly PluginManagerInterface $formatterPluginManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
  ) {
  }

  /**
   * Sets the deprecations enabling status.
   *
   * @param bool $enabled
   *   Whether deprecations should be enabled.
   */
  public function setDeprecationsEnabled(bool $enabled): void {
    $this->deprecationsEnabled = $enabled;
  }

  /**
   * Whether deprecations are enabled.
   */
  public function areDeprecationsEnabled(): bool {
    return $this->deprecationsEnabled;
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
      if ($this->processRememberRolesUpdate($handler, $handler_type)) {
        $changed = TRUE;
      }
      if ($this->processTableCssClassUpdate($view)) {
        $changed = TRUE;
      }
      if ($this->processBlockContentListingEmptyUpdate($view)) {
        $changed = TRUE;
      }
      if ($this->processRssViewModeUpdate($view)) {
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
    if ($this->areDeprecationsEnabled() && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The update to convert "numeric" arguments to "entity_target_id" for entity reference fields for view "%s" is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3441945', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Checks for fields with the format plural option set.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   TRUE if view has fields with the format plural option.
   */
  public function needsFormatPluralUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, FALSE, function (&$handler, $handler_type) {
      return $this->processFieldHandlerWithFormatPlural($handler, $handler_type);
    });
  }

  /**
   * Processes fields with the format plural option set.
   *
   * This option is only set for fields using an aggregation function such as
   * COUNT or SUM. The data type is changed so it matches the field schema.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processFieldHandlerWithFormatPlural(array &$handler, string $handler_type): bool {
    // Force view re-save if the format plural option exists.
    if ($handler_type === 'field' && isset($handler['format_plural'])) {
      // Cast to the correct data type. This changes 1/0 to true/false.
      $handler['format_plural'] = (bool) $handler['format_plural'];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if 'remember_roles' setting of an exposed filter has disabled roles.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   TRUE if the view has any disabled roles.
   */
  public function needsRememberRolesUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) {
      return $this->processRememberRolesUpdate($handler, $handler_type);
    });
  }

  /**
   * Processes filters and removes disabled remember roles.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  public function processRememberRolesUpdate(array &$handler, string $handler_type): bool {
    if ($handler_type === 'filter' && !empty($handler['expose']['remember_roles'])) {
      $needsUpdate = FALSE;
      foreach (array_keys($handler['expose']['remember_roles'], '0', TRUE) as $role_key) {
        unset($handler['expose']['remember_roles'][$role_key]);
        $needsUpdate = TRUE;
      }
      return $needsUpdate;
    }
    return FALSE;
  }

  /**
   * Checks for table style views needing a default CSS table class value.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   TRUE if the view has any table styles that need to have
   *   a default table CSS class added.
   */
  public function needsTableCssClassUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processTableCssClassUpdate($view);
    });
  }

  /**
   * Processes views and adds default CSS table class value if necessary.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   TRUE if the view was updated with a default table CSS class value.
   */
  public function processTableCssClassUpdate(ViewEntityInterface $view): bool {
    $changed = FALSE;
    $displays = $view->get('display');

    foreach ($displays as &$display) {
      if (
        isset($display['display_options']['style']) &&
        $display['display_options']['style']['type'] === 'table' &&
        isset($display['display_options']['style']['options']) &&
        !isset($display['display_options']['style']['options']['class'])
      ) {
        $display['display_options']['style']['options']['class'] = '';
        $changed = TRUE;
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    $deprecations_triggered = &$this->triggeredDeprecations['table_css_class'][$view->id()];
    if ($this->areDeprecationsEnabled() && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The update to add a default table CSS class for view "%s" is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3499943', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Checks if 'block_content_listing_empty' needs to be removed.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   TRUE if the view has the plugin.
   */
  public function needsBlockContentListingEmptyUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processBlockContentListingEmptyUpdate($view);
    });
  }

  /**
   * Processes area plugins and removes block_content_listing_empty.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  public function processBlockContentListingEmptyUpdate(ViewEntityInterface $view): bool {
    $changed = FALSE;
    $displays = $view->get('display');

    foreach ($displays as &$display) {
      foreach ($display['display_options']['empty'] ?? [] as $id => $emptyOptions) {
        if ($emptyOptions['id'] === 'block_content_listing_empty') {
          $changed = TRUE;
          unset($display['display_options']['empty'][$id]);
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    $deprecations_triggered = &$this->triggeredDeprecations['block_content_listing_empty'][$view->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The update to remove the block_content_listing_empty plugin from view "%s" is deprecated in drupal:11.3.0 and is removed from drupal:13.0.0. Profile, module and theme provided configuration should be updated. See https://www.drupal.org/node/3336219', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Checks for views needing a default RSS view mode.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   * @param string|null $previous_view_mode
   *   The previous view mode.
   *
   * @return bool
   *   TRUE if the view has been updated.
   */
  public function needsRssViewModeUpdate(ViewEntityInterface $view, ?string $previous_view_mode = NULL): bool {
    return $this->processRssViewModeUpdate($view, $previous_view_mode);
  }

  /**
   * Processes views and sets the default RSS view mode if necessary.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   * @param string|null $previous_view_mode
   *   The previous view mode.
   *
   * @return bool
   *   TRUE if the view was updated with a default RSS view mode.
   */
  public function processRssViewModeUpdate(ViewEntityInterface $view, ?string $previous_view_mode = NULL) : bool {
    $changed = FALSE;
    $displays = $view->get('display');

    // Row types that need updating.
    $row_types = [
      'comment_rss' => 'comment',
      'node_rss' => 'node',
    ];

    foreach ($displays as &$display) {
      if (isset($display['display_options']['row']['options']['view_mode']) &&
        array_key_exists($display['display_options']['row']['type'], $row_types) &&
        $display['display_options']['row']['options']['view_mode'] === 'default') {

        // When system.rss is already removed but a view is saved, we still need
        // to try and set the view_mode to something more sane. But detecting
        // if the view mode was always default, or default because it used the
        // system.rss setting is hard. So if there is a default mode available
        // it will use that.
        // It would make sense to use any RSS view_mode if available, but that
        // would mean 'default' can never be set as a view mode. That is an
        // issue, therefore, if we have a default view mode available, we will
        // use that.
        if ($previous_view_mode === NULL) {
          $view_modes = $this->entityDisplayRepository->getViewModes($row_types[$display['display_options']['row']['type']]);
          if (array_key_exists('default', $view_modes)) {
            return FALSE;
          }

          // If there is no default, the most likely view mode is RSS. If that
          // is available we use that. Otherwise, fall back to the first
          // available.
          $probable_view_mode = isset($view_modes['rss']) ? 'rss' : array_key_first($view_modes);
          $display['display_options']['row']['options']['view_mode'] = $probable_view_mode;
          $changed = TRUE;
        }
        else {
          $display['display_options']['row']['options']['view_mode'] = $previous_view_mode;
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    return $changed;
  }

}
