<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Entity\EntityViewDisplay.
 */

namespace Drupal\Core\Entity\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayPluginCollection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityDisplayBase;

/**
 * Configuration entity that contains display options for all components of a
 * rendered entity in a given view mode.
 *
 * @ConfigEntityType(
 *   id = "entity_view_display",
 *   label = @Translation("Entity view display"),
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "bundle",
 *     "mode",
 *     "content",
 *     "hidden",
 *   }
 * )
 */
class EntityViewDisplay extends EntityDisplayBase implements EntityViewDisplayInterface {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'view';

  /**
   * Returns the display objects used to render a set of entities.
   *
   * Depending on the configuration of the view mode for each bundle, this can
   * be either the display object associated with the view mode, or the
   * 'default' display.
   *
   * This method should only be used internally when rendering an entity. When
   * assigning suggested display options for a component in a given view mode,
   * entity_get_display() should be used instead, in order to avoid
   * inadvertently modifying the output of other view modes that might happen to
   * use the 'default' display too. Those options will then be effectively
   * applied only if the view mode is configured to use them.
   *
   * hook_entity_view_display_alter() is invoked on each display, allowing 3rd
   * party code to alter the display options held in the display before they are
   * used to generate render arrays.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $entities
   *   The entities being rendered. They should all be of the same entity type.
   * @param string $view_mode
   *   The view mode being rendered.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface[]
   *   The display objects to use to render the entities, keyed by entity
   *   bundle.
   *
   * @see entity_get_display()
   * @see hook_entity_view_display_alter()
   */
  public static function collectRenderDisplays($entities, $view_mode) {
    if (empty($entities)) {
      return array();
    }

    // Collect entity type and bundles.
    $entity_type = current($entities)->getEntityTypeId();
    $bundles = array();
    foreach ($entities as $entity) {
      $bundles[$entity->bundle()] = TRUE;
    }
    $bundles = array_keys($bundles);

    // For each bundle, check the existence and status of:
    // - the display for the view mode,
    // - the 'default' display.
    $candidate_ids = array();
    foreach ($bundles as $bundle) {
      if ($view_mode != 'default') {
        $candidate_ids[$bundle][] = $entity_type . '.' . $bundle . '.' . $view_mode;
      }
      $candidate_ids[$bundle][] = $entity_type . '.' . $bundle . '.default';
    }
    $results = \Drupal::entityQuery('entity_view_display')
      ->condition('id', NestedArray::mergeDeepArray($candidate_ids))
      ->condition('status', TRUE)
      ->execute();

    // For each bundle, select the first valid candidate display, if any.
    $load_ids = array();
    foreach ($bundles as $bundle) {
      foreach ($candidate_ids[$bundle] as $candidate_id) {
        if (isset($results[$candidate_id])) {
          $load_ids[$bundle] = $candidate_id;
          break;
        }
      }
    }

    // Load the selected displays.
    $storage = \Drupal::entityManager()->getStorage('entity_view_display');
    $displays = $storage->loadMultiple($load_ids);

    $displays_by_bundle = array();
    foreach ($bundles as $bundle) {
      // Use the selected display if any, or create a fresh runtime object.
      if (isset($load_ids[$bundle])) {
        $display = $displays[$load_ids[$bundle]];
      }
      else {
        $display = $storage->create(array(
          'targetEntityType' => $entity_type,
          'bundle' => $bundle,
          'mode' => $view_mode,
          'status' => TRUE,
        ));
      }

      // Let the display know which view mode was originally requested.
      $display->originalMode = $view_mode;

      // Let modules alter the display.
      $display_context = array(
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'view_mode' => $view_mode,
      );
      \Drupal::moduleHandler()->alter('entity_view_display', $display, $display_context);

      $displays_by_bundle[$bundle] = $display;
    }

    return $displays_by_bundle;
  }

  /**
   * Returns the display object used to render an entity.
   *
   * See the collectRenderDisplays() method for details.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being rendered.
   * @param string $view_mode
   *   The view mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The display object that should be used to render the entity.
   *
   * @see \Drupal\Core\Entity\Entity\EntityViewDisplay::collectRenderDisplays()
   */
  public static function collectRenderDisplay(FieldableEntityInterface $entity, $view_mode) {
    $displays = static::collectRenderDisplays(array($entity), $view_mode);
    return $displays[$entity->bundle()];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    $this->pluginManager = \Drupal::service('plugin.manager.field.formatter');

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Reset the render cache for the target entity type.
    parent::postSave($storage, $update);
    if (\Drupal::entityManager()->hasHandler($this->targetEntityType, 'view_builder')) {
      \Drupal::entityManager()->getViewBuilder($this->targetEntityType)->resetCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer($field_name) {
    if (isset($this->plugins[$field_name])) {
      return $this->plugins[$field_name];
    }

    // Instantiate the formatter object from the stored display properties.
    if (($configuration = $this->getComponent($field_name)) && isset($configuration['type']) && ($definition = $this->getFieldDefinition($field_name))) {
      $formatter = $this->pluginManager->getInstance(array(
        'field_definition' => $definition,
        'view_mode' => $this->originalMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration
      ));
    }
    else {
      $formatter = NULL;
    }

    // Persist the formatter object.
    $this->plugins[$field_name] = $formatter;
    return $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FieldableEntityInterface $entity) {
    $build = $this->buildMultiple(array($entity));
    return $build[0];
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $entities) {
    $build_list = array();
    foreach ($entities as $key => $entity) {
      $build_list[$key] = array();
    }

    // Run field formatters.
    foreach ($this->getComponents() as $name => $options) {
      if ($formatter = $this->getRenderer($name)) {
        // Group items across all entities and pass them to the formatter's
        // prepareView() method.
        $grouped_items = array();
        foreach ($entities as $id => $entity) {
          $items = $entity->get($name);
          $items->filterEmptyItems();
          $grouped_items[$id] = $items;
        }
        $formatter->prepareView($grouped_items);

        // Then let the formatter build the output for each entity.
        foreach ($entities as $id => $entity) {
          $items = $grouped_items[$id];
          /** @var \Drupal\Core\Access\AccessResultInterface $field_access */
          $field_access = $items->access('view', NULL, TRUE);
          $build_list[$id][$name] = $field_access->isAllowed() ? $formatter->view($items, $entity->language()->getId()) : [];
          // Apply the field access cacheability metadata to the render array.
          $this->renderer->addCacheableDependency($build_list[$id][$name], $field_access);
        }
      }
    }

    foreach ($entities as $id => $entity) {
      // Assign the configured weights.
      foreach ($this->getComponents() as $name => $options) {
        if (isset($build_list[$id][$name])) {
          $build_list[$id][$name]['#weight'] = $options['weight'];
        }
      }

      // Let other modules alter the renderable array.
      $context = array(
        'entity' => $entity,
        'view_mode' => $this->originalMode,
        'display' => $this,
      );
      \Drupal::moduleHandler()->alter('entity_display_build', $build_list[$key], $context);
    }

    return $build_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    $configurations = array();
    foreach ($this->getComponents() as $field_name => $configuration) {
      if (!empty($configuration['type']) && ($field_definition = $this->getFieldDefinition($field_name))) {
        $configurations[$configuration['type']] = $configuration + array(
          'field_definition' => $field_definition,
          'view_mode' => $this->originalMode,
        );
      }
    }

    return array(
      'formatters' => new EntityDisplayPluginCollection($this->pluginManager, $configurations)
    );
  }
}
