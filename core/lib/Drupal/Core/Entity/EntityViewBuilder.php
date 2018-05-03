<?php

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Theme\Registry;
use Drupal\Core\TypedData\TranslatableInterface as TranslatableDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity view builders.
 *
 * @ingroup entity_api
 */
class EntityViewBuilder extends EntityHandlerBase implements EntityHandlerInterface, EntityViewBuilderInterface {

  /**
   * The type of entities for which this view builder is instantiated.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The cache bin used to store the render cache.
   *
   * @var string
   */
  protected $cacheBin = 'render';

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The theme registry.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * The EntityViewDisplay objects created for individual field rendering.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[]
   *
   * @see \Drupal\Core\Entity\EntityViewBuilder::getSingleFieldDisplay()
   */
  protected $singleFieldDisplays;

  /**
   * Constructs a new EntityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, Registry $theme_registry = NULL) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    $this->themeRegistry = $theme_registry ?: \Drupal::service('theme.registry');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('theme.registry')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build_list = $this->viewMultiple([$entity], $view_mode, $langcode);

    // The default ::buildMultiple() #pre_render callback won't run, because we
    // extract a child element of the default renderable array. Thus we must
    // assign an alternative #pre_render callback that applies the necessary
    // transformations and then still calls ::buildMultiple().
    $build = $build_list[0];
    $build['#pre_render'][] = [$this, 'build'];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build_list = [
      '#sorted' => TRUE,
      '#pre_render' => [[$this, 'buildMultiple']],
    ];
    $weight = 0;
    foreach ($entities as $key => $entity) {
      // Ensure that from now on we are dealing with the proper translation
      // object.
      $entity = $this->entityManager->getTranslationFromContext($entity, $langcode);

      // Set build defaults.
      $build_list[$key] = $this->getBuildDefaults($entity, $view_mode);
      $entityType = $this->entityTypeId;
      $this->moduleHandler()->alter([$entityType . '_build_defaults', 'entity_build_defaults'], $build_list[$key], $entity, $view_mode);

      $build_list[$key]['#weight'] = $weight++;
    }

    return $build_list;
  }

  /**
   * Provides entity-specific defaults to the build process.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the defaults should be provided.
   * @param string $view_mode
   *   The view mode that should be used.
   *
   * @return array
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    // Allow modules to change the view mode.
    $context = [];
    $this->moduleHandler()->alter('entity_view_mode', $view_mode, $entity, $context);

    $build = [
      "#{$this->entityTypeId}" => $entity,
      '#view_mode' => $view_mode,
      // Collect cache defaults for this entity.
      '#cache' => [
        'tags' => Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags()),
        'contexts' => $entity->getCacheContexts(),
        'max-age' => $entity->getCacheMaxAge(),
      ],
    ];

    // Add the default #theme key if a template exists for it.
    if ($this->themeRegistry->getRuntime()->has($this->entityTypeId)) {
      $build['#theme'] = $this->entityTypeId;
    }

    // Cache the rendered output if permitted by the view mode and global entity
    // type configuration.
    if ($this->isViewModeCacheable($view_mode) && !$entity->isNew() && $entity->isDefaultRevision() && $this->entityType->isRenderCacheable()) {
      $build['#cache'] += [
        'keys' => [
          'entity_view',
          $this->entityTypeId,
          $entity->id(),
          $view_mode,
        ],
        'bin' => $this->cacheBin,
      ];

      if ($entity instanceof TranslatableDataInterface && count($entity->getTranslationLanguages()) > 1) {
        $build['#cache']['keys'][] = $entity->language()->getId();
      }
    }

    return $build;
  }

  /**
   * Builds an entity's view; augments entity defaults.
   *
   * This function is assigned as a #pre_render callback in ::view().
   *
   * It transforms the renderable array for a single entity to the same
   * structure as if we were rendering multiple entities, and then calls the
   * default ::buildMultiple() #pre_render callback.
   *
   * @param array $build
   *   A renderable array containing build information and context for an entity
   *   view.
   *
   * @return array
   *   The updated renderable array.
   *
   * @see \Drupal\Core\Render\RendererInterface::render()
   */
  public function build(array $build) {
    $build_list = [$build];
    $build_list = $this->buildMultiple($build_list);
    return $build_list[0];
  }

  /**
   * Builds multiple entities' views; augments entity defaults.
   *
   * This function is assigned as a #pre_render callback in ::viewMultiple().
   *
   * By delaying the building of an entity until the #pre_render processing in
   * drupal_render(), the processing cost of assembling an entity's renderable
   * array is saved on cache-hit requests.
   *
   * @param array $build_list
   *   A renderable  array containing build information and context for an
   *   entity view.
   *
   * @return array
   *   The updated renderable array.
   *
   * @see \Drupal\Core\Render\RendererInterface::render()
   */
  public function buildMultiple(array $build_list) {
    // Build the view modes and display objects.
    $view_modes = [];
    $entity_type_key = "#{$this->entityTypeId}";
    $view_hook = "{$this->entityTypeId}_view";

    // Find the keys for the ContentEntities in the build; Store entities for
    // rendering by view_mode.
    $children = Element::children($build_list);
    foreach ($children as $key) {
      if (isset($build_list[$key][$entity_type_key])) {
        $entity = $build_list[$key][$entity_type_key];
        if ($entity instanceof FieldableEntityInterface) {
          $view_modes[$build_list[$key]['#view_mode']][$key] = $entity;
        }
      }
    }

    // Build content for the displays represented by the entities.
    foreach ($view_modes as $view_mode => $view_mode_entities) {
      $displays = EntityViewDisplay::collectRenderDisplays($view_mode_entities, $view_mode);
      $this->buildComponents($build_list, $view_mode_entities, $displays, $view_mode);
      foreach (array_keys($view_mode_entities) as $key) {
        // Allow for alterations while building, before rendering.
        $entity = $build_list[$key][$entity_type_key];
        $display = $displays[$entity->bundle()];

        $this->moduleHandler()->invokeAll($view_hook, [&$build_list[$key], $entity, $display, $view_mode]);
        $this->moduleHandler()->invokeAll('entity_view', [&$build_list[$key], $entity, $display, $view_mode]);

        $this->addContextualLinks($build_list[$key], $entity);
        $this->alterBuild($build_list[$key], $entity, $display, $view_mode);

        // Assign the weights configured in the display.
        // @todo: Once https://www.drupal.org/node/1875974 provides the missing
        //   API, only do it for 'extra fields', since other components have
        //   been taken care of in EntityViewDisplay::buildMultiple().
        foreach ($display->getComponents() as $name => $options) {
          if (isset($build_list[$key][$name])) {
            $build_list[$key][$name]['#weight'] = $options['weight'];
          }
        }

        // Allow modules to modify the render array.
        $this->moduleHandler()->alter([$view_hook, 'entity_view'], $build_list[$key], $entity, $display);
      }
    }

    return $build_list;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    $entities_by_bundle = [];
    foreach ($entities as $id => $entity) {
      // Initialize the field item attributes for the fields being displayed.
      // The entity can include fields that are not displayed, and the display
      // can include components that are not fields, so we want to act on the
      // intersection. However, the entity can have many more fields than are
      // displayed, so we avoid the cost of calling $entity->getProperties()
      // by iterating the intersection as follows.
      foreach ($displays[$entity->bundle()]->getComponents() as $name => $options) {
        if ($entity->hasField($name)) {
          foreach ($entity->get($name) as $item) {
            $item->_attributes = [];
          }
        }
      }
      // Group the entities by bundle.
      $entities_by_bundle[$entity->bundle()][$id] = $entity;
    }

    // Invoke hook_entity_prepare_view().
    $this->moduleHandler()->invokeAll('entity_prepare_view', [$this->entityTypeId, $entities, $displays, $view_mode]);

    // Let the displays build their render arrays.
    foreach ($entities_by_bundle as $bundle => $bundle_entities) {
      $display_build = $displays[$bundle]->buildMultiple($bundle_entities);
      foreach ($bundle_entities as $id => $entity) {
        $build[$id] += $display_build[$id];
      }
    }
  }

  /**
   * Add contextual links.
   *
   * @param array $build
   *   The render array that is being created.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be prepared.
   */
  protected function addContextualLinks(array &$build, EntityInterface $entity) {
    if ($entity->isNew()) {
      return;
    }
    $key = $entity->getEntityTypeId();
    $rel = 'canonical';
    if ($entity instanceof ContentEntityInterface && !$entity->isDefaultRevision()) {
      $rel = 'revision';
      $key .= '_revision';
    }
    if ($entity->hasLinkTemplate($rel)) {
      $build['#contextual_links'][$key] = [
        'route_parameters' => $entity->toUrl($rel)->getRouteParameters(),
      ];
      if ($entity instanceof EntityChangedInterface) {
        $build['#contextual_links'][$key]['metadata'] = [
          'changed' => $entity->getChangedTime(),
        ];
      }
    }
  }

  /**
   * Specific per-entity building.
   *
   * @param array $build
   *   The render array that is being created.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be prepared.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   * @param string $view_mode
   *   The view mode that should be used to prepare the entity.
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {}

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [$this->entityTypeId . '_view'];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // If no set of specific entities is provided, invalidate the entity view
    // builder's cache tag. This will invalidate all entities rendered by this
    // view builder.
    // Otherwise, if a set of specific entities is provided, invalidate those
    // specific entities only, plus their list cache tags, because any lists in
    // which these entities are rendered, must be invalidated as well. However,
    // even in this case, we might invalidate more cache items than necessary.
    // When we have a way to invalidate only those cache items that have both
    // the individual entity's cache tag and the view builder's cache tag, we'll
    // be able to optimize this further.
    if (isset($entities)) {
      $tags = [];
      foreach ($entities as $entity) {
        $tags = Cache::mergeTags($tags, $entity->getCacheTags());
        $tags = Cache::mergeTags($tags, $entity->getEntityType()->getListCacheTags());
      }
      Cache::invalidateTags($tags);
    }
    else {
      Cache::invalidateTags($this->getCacheTags());
    }
  }

  /**
   * Determines whether the view mode is cacheable.
   *
   * @param string $view_mode
   *   Name of the view mode that should be rendered.
   *
   * @return bool
   *   TRUE if the view mode can be cached, FALSE otherwise.
   */
  protected function isViewModeCacheable($view_mode) {
    if ($view_mode == 'default') {
      // The 'default' is not an actual view mode.
      return TRUE;
    }
    $view_modes_info = $this->entityManager->getViewModes($this->entityTypeId);
    return !empty($view_modes_info[$view_mode]['cache']);
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = []) {
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();
    $display = $this->getSingleFieldDisplay($entity, $field_name, $display_options);

    $output = [];
    $build = $display->build($entity);
    if (isset($build[$field_name])) {
      $output = $build[$field_name];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display = []) {
    $entity = $item->getEntity();
    $field_name = $item->getFieldDefinition()->getName();

    // Clone the entity since we are going to modify field values.
    $clone = clone $entity;

    // Push the item as the single value for the field, and defer to viewField()
    // to build the render array for the whole list.
    $clone->{$field_name}->setValue([$item->getValue()]);
    $elements = $this->viewField($clone->{$field_name}, $display);

    // Extract the part of the render array we need.
    $output = isset($elements[0]) ? $elements[0] : [];
    if (isset($elements['#access'])) {
      $output['#access'] = $elements['#access'];
    }

    return $output;
  }

  /**
   * Gets an EntityViewDisplay for rendering an individual field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   * @param string|array $display_options
   *   The display options passed to the viewField() method.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected function getSingleFieldDisplay($entity, $field_name, $display_options) {
    if (is_string($display_options)) {
      // View mode: use the Display configured for the view mode.
      $view_mode = $display_options;
      $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      // Hide all fields except the current one.
      foreach (array_keys($entity->getFieldDefinitions()) as $name) {
        if ($name != $field_name) {
          $display->removeComponent($name);
        }
      }
    }
    else {
      // Array of custom display options: use a runtime Display for the
      // '_custom' view mode. Persist the displays created, to reduce the number
      // of objects (displays and formatter plugins) created when rendering a
      // series of fields individually for cases such as views tables.
      $entity_type_id = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $key = $entity_type_id . ':' . $bundle . ':' . $field_name . ':' . Crypt::hashBase64(serialize($display_options));
      if (!isset($this->singleFieldDisplays[$key])) {
        $this->singleFieldDisplays[$key] = EntityViewDisplay::create([
          'targetEntityType' => $entity_type_id,
          'bundle' => $bundle,
          'status' => TRUE,
        ])->setComponent($field_name, $display_options);
      }
      $display = $this->singleFieldDisplays[$key];
    }

    return $display;
  }

}
