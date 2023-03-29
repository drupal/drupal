<?php

namespace Drupal\views;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
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
   * @param \Drupal\Component\Plugin\PluginManagerInterface $formatter_plugin_manager
   *   The formatter plugin manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    TypedConfigManagerInterface $typed_config_manager,
    ViewsData $views_data,
    PluginManagerInterface $formatter_plugin_manager
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
      if ($this->processResponsiveImageLazyLoadFieldHandler($handler, $handler_type, $view)) {
        $changed = TRUE;
      }
      if ($this->processTimestampFormatterTimeDiffUpdateHandler($handler, $handler_type)) {
        $changed = TRUE;
      }
      if ($this->processRevisionFieldHyphenFix($view)) {
        $changed = TRUE;
      }
      return $changed;
    });
  }

  /**
   * Add lazy load options to all responsive_image type field configurations.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function needsResponsiveImageLazyLoadFieldUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processResponsiveImageLazyLoadFieldHandler($handler, $handler_type, $view);
    });
  }

  /**
   * Processes responsive_image type fields.
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
  protected function processResponsiveImageLazyLoadFieldHandler(array &$handler, string $handler_type, ViewEntityInterface $view): bool {
    $changed = FALSE;

    // Add any missing settings for lazy loading.
    if (($handler_type === 'field')
      && isset($handler['plugin_id'], $handler['type'])
      && $handler['plugin_id'] === 'field'
      && $handler['type'] === 'responsive_image'
      && !isset($handler['settings']['image_loading'])) {
      $handler['settings']['image_loading'] = ['attribute' => 'eager'];
      $changed = TRUE;
    }

    return $changed;
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
   * Add eager load option to all oembed type field configurations.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function needsOembedEagerLoadFieldUpdate(ViewEntityInterface $view) {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processOembedEagerLoadFieldHandler($handler, $handler_type, $view);
    });
  }

  /**
   * Processes oembed type fields.
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
  protected function processOembedEagerLoadFieldHandler(array &$handler, string $handler_type, ViewEntityInterface $view): bool {
    $changed = FALSE;

    // Add any missing settings for lazy loading.
    if (($handler_type === 'field')
      && isset($handler['plugin_id'], $handler['type'])
      && $handler['plugin_id'] === 'field'
      && $handler['type'] === 'oembed'
      && !array_key_exists('loading', $handler['settings'])) {
      $handler['settings']['loading'] = ['attribute' => 'eager'];
      $changed = TRUE;
    }

    $deprecations_triggered = &$this->triggeredDeprecations['3212351'][$view->id()];
    if ($this->deprecationsEnabled && $changed && !$deprecations_triggered) {
      $deprecations_triggered = TRUE;
      @trigger_error(sprintf('The oEmbed loading attribute update for view "%s" is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Profile, module and theme provided configuration should be updated to accommodate the changes described at https://www.drupal.org/node/3275103.', $view->id()), E_USER_DEPRECATED);
    }

    return $changed;
  }

  /**
   * Updates the timestamp fields settings by adding time diff and tooltip.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function needsTimestampFormatterTimeDiffUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (array &$handler, string $handler_type): bool {
      return $this->processTimestampFormatterTimeDiffUpdateHandler($handler, $handler_type);
    });
  }

  /**
   * Processes timestamp fields settings by adding time diff and tooltip.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   *
   * @return bool
   *   Whether the handler was updated.
   */
  protected function processTimestampFormatterTimeDiffUpdateHandler(array &$handler, string $handler_type): bool {
    if ($handler_type === 'field' && isset($handler['type'])) {
      $plugin_definition = $this->formatterPluginManager->getDefinition($handler['type'], FALSE);
      // Check also potential plugins extending TimestampFormatter.
      if (!$plugin_definition || !is_a($plugin_definition['class'], TimestampFormatter::class, TRUE)) {
        return FALSE;
      }

      if (!isset($handler['settings']['tooltip']) || !isset($handler['settings']['time_diff'])) {
        $handler['settings'] += $plugin_definition['class']::defaultSettings();
        // Existing timestamp formatters don't have tooltip.
        $handler['settings']['tooltip'] = [
          'date_format' => '',
          'custom_date_format' => '',
        ];
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Replaces hyphen on historical data (revision) fields.
   *
   * This replaces hyphens with double underscores in twig assertions.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   Whether the handler was updated.
   *
   * @see https://www.drupal.org/project/drupal/issues/2831233
   */
  public function processRevisionFieldHyphenFix(ViewEntityInterface $view): bool {
    // Regex to search only for token with machine name '-revision_id'.
    $old_part = '/{{([^}]+)(-revision_id)/';
    $new_part = '{{$1__revision_id';
    $old_field = '-revision_id';
    $new_field = '__revision_id';
    /** @var \Drupal\views\ViewEntityInterface $view */
    $is_update = FALSE;
    $displays = $view->get('display');
    foreach ($displays as &$display) {
      if (isset($display['display_options']['fields'])) {
        foreach ($display['display_options']['fields'] as $field_name => $field) {
          if (!empty($field['alter']['text'])) {
            // Fixes replacement token references in rewritten fields.
            $alter_text = $field['alter']['text'];
            if (preg_match($old_part, $alter_text) === 1) {
              $is_update = TRUE;
              $field['alter']['text'] = preg_replace($old_part, $new_part, $alter_text);
            }
          }

          if (!empty($field['alter']['path'])) {
            // Fixes replacement token references in link paths.
            $alter_path = $field['alter']['path'];
            if (preg_match($old_part, $alter_path) === 1) {
              $is_update = TRUE;
              $field['alter']['path'] = preg_replace($old_part, $new_part, $alter_path);
            }
          }

          if (str_contains($field_name, $old_field)) {
            // Replaces the field name and the view id.
            $is_update = TRUE;
            $field['id'] = str_replace($old_field, $new_field, $field['id']);
            $field['field'] = str_replace($old_field, $new_field, $field['field']);

            // Replace key with save order.
            $field_name_update = str_replace($old_field, $new_field, $field_name);
            $fields = $display['display_options']['fields'];
            $keys = array_keys($fields);
            $keys[array_search($field_name, $keys)] = $field_name_update;
            $display['display_options']['fields'] = array_combine($keys, $fields);
            $display['display_options']['fields'][$field_name_update] = $field;
          }
        }
      }
    }
    if ($is_update) {
      $view->set('display', $displays);
    }
    return $is_update;
  }

  /**
   * Checks each display in a view to see if it needs the hyphen fix.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view entity.
   *
   * @return bool
   *   TRUE if the view has any displays that needed to be updated.
   */
  public function needsRevisionFieldHyphenFix(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (&$handler, $handler_type) use ($view) {
      return $this->processRevisionFieldHyphenFix($view);
    });
  }

}
