<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityViewBuilder.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity\Entity\EntityViewDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity view controllers.
 */
class EntityViewBuilder extends EntityControllerBase implements EntityControllerInterface, EntityViewBuilderInterface {

  /**
   * The type of entities for which this controller is instantiated.
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
   * @todo Defaults to 'cache' for now, until http://drupal.org/node/1194136 is
   * fixed.
   *
   * @var string
   */
  protected $cacheBin = 'cache';

  /**
   * The language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * Constructs a new EntityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    $entities_by_bundle = array();
    foreach ($entities as $id => $entity) {
      // Remove previously built content, if exists.
      $entity->content = array(
        '#view_mode' => $view_mode,
      );
      // Initialize the field item attributes for the fields being displayed.
      // The entity can include fields that are not displayed, and the display
      // can include components that are not fields, so we want to act on the
      // intersection. However, the entity can have many more fields than are
      // displayed, so we avoid the cost of calling $entity->getProperties()
      // by iterating the intersection as follows.
      foreach ($displays[$entity->bundle()]->getComponents() as $name => $options) {
        if ($entity->hasField($name)) {
          foreach ($entity->get($name) as $item) {
            $item->_attributes = array();
          }
        }
      }
      // Group the entities by bundle.
      $entities_by_bundle[$entity->bundle()][$id] = $entity;
    }

    // Invoke hook_entity_prepare_view().
    \Drupal::moduleHandler()->invokeAll('entity_prepare_view', array($this->entityTypeId, $entities, $displays, $view_mode));

    // Let the displays build their render arrays.
    foreach ($entities_by_bundle as $bundle => $bundle_entities) {
      $build = $displays[$bundle]->buildMultiple($bundle_entities);
      foreach ($bundle_entities as $id => $entity) {
        $entity->content += $build[$id];
      }
    }
  }

  /**
   * Provides entity-specific defaults to the build process.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the defaults should be provided.
   * @param string $view_mode
   *   The view mode that should be used.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   *
   * @return array
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $return = array(
      '#theme' => $this->entityTypeId,
      "#{$this->entityTypeId}" => $entity,
      '#view_mode' => $view_mode,
      '#langcode' => $langcode,
      '#cache' => array(
        'tags' =>  array(
          $this->entityTypeId . '_view' => TRUE,
          $this->entityTypeId => array($entity->id()),
        ),
      )
    );

    // Cache the rendered output if permitted by the view mode and global entity
    // type configuration.
    if ($this->isViewModeCacheable($view_mode) && !$entity->isNew() && $entity->isDefaultRevision() && $this->entityType->isRenderCacheable()) {
      $return['#cache'] += array(
        'keys' => array('entity_view', $this->entityTypeId, $entity->id(), $view_mode),
        'granularity' => DRUPAL_CACHE_PER_ROLE,
        'bin' => $this->cacheBin,
      );

      if ($entity instanceof TranslatableInterface && count($entity->getTranslationLanguages()) > 1) {
        $return['#cache']['keys'][] = $langcode;
      }
    }

    return $return;
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
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode, $langcode = NULL) { }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $buildList = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return $buildList[0];
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    if (!isset($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage(Language::TYPE_CONTENT)->id;
    }

    // Build the view modes and display objects.
    $view_modes = array();
    $context = array('langcode' => $langcode);
    foreach ($entities as $key => $entity) {
      $bundle = $entity->bundle();

      // Ensure that from now on we are dealing with the proper translation
      // object.
      $entity = $this->entityManager->getTranslationFromContext($entity, $langcode);
      $entities[$key] = $entity;

      // Allow modules to change the view mode.
      $entity_view_mode = $view_mode;
      $this->moduleHandler->alter('entity_view_mode', $entity_view_mode, $entity, $context);
      // Store entities for rendering by view_mode.
      $view_modes[$entity_view_mode][$entity->id()] = $entity;
    }

    foreach ($view_modes as $mode => $view_mode_entities) {
      $displays[$mode] = EntityViewDisplay::collectRenderDisplays($view_mode_entities, $mode);
      $this->buildContent($view_mode_entities, $displays[$mode], $mode, $langcode);
    }

    $view_hook = "{$this->entityTypeId}_view";
    $build = array('#sorted' => TRUE);
    $weight = 0;
    foreach ($entities as $key => $entity) {
      $entity_view_mode = isset($entity->content['#view_mode']) ? $entity->content['#view_mode'] : $view_mode;
      $display = $displays[$entity_view_mode][$entity->bundle()];
      \Drupal::moduleHandler()->invokeAll($view_hook, array($entity, $display, $entity_view_mode, $langcode));
      \Drupal::moduleHandler()->invokeAll('entity_view', array($entity, $display, $entity_view_mode, $langcode));

      $build[$key] = $entity->content;
      // We don't need duplicate rendering info in $entity->content.
      unset($entity->content);

      $build[$key] += $this->getBuildDefaults($entity, $entity_view_mode, $langcode);
      $this->alterBuild($build[$key], $entity, $display, $entity_view_mode, $langcode);

      // Assign the weights configured in the display.
      // @todo: Once https://drupal.org/node/1875974 provides the missing API,
      //   only do it for 'extra fields', since other components have been taken
      //   care of in EntityViewDisplay::buildMultiple().
      foreach ($display->getComponents() as $name => $options) {
        if (isset($build[$key][$name])) {
          $build[$key][$name]['#weight'] = $options['weight'];
        }
      }

      $build[$key]['#weight'] = $weight++;

      // Allow modules to modify the render array.
      $this->moduleHandler->alter(array($view_hook, 'entity_view'), $build[$key], $entity, $display);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    if (isset($entities)) {
      $tags = array();
      foreach ($entities as $entity) {
        $id = $entity->id();
        $tags[$this->entityTypeId][$id] = $id;
        $tags[$this->entityTypeId . '_view_' . $entity->bundle()] = TRUE;
      }
      Cache::deleteTags($tags);
    }
    else {
      Cache::deleteTags(array($this->entityTypeId . '_view' => TRUE));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = array()) {
    $output = array();
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();

    // Get the display object.
    if (is_string($display_options)) {
      $view_mode = $display_options;
      $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      foreach ($entity as $name => $items) {
        if ($name != $field_name) {
          $display->removeComponent($name);
        }
      }
    }
    else {
      $view_mode = '_custom';
      $display = entity_create('entity_view_display', array(
        'targetEntityType' => $entity->getEntityTypeId(),
        'bundle' => $entity->bundle(),
        'mode' => $view_mode,
        'status' => TRUE,
      ));
      $display->setComponent($field_name, $display_options);
    }

    $build = $display->build($entity);
    if (isset($build[$field_name])) {
      $output = $build[$field_name];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display = array()) {
    $entity = $item->getEntity();
    $field_name = $item->getFieldDefinition()->getName();

    // Clone the entity since we are going to modify field values.
    $clone = clone $entity;

    // Push the item as the single value for the field, and defer to viewField()
    // to build the render array for the whole list.
    $clone->{$field_name}->setValue(array($item->getValue()));
    $elements = $this->viewField($clone->{$field_name}, $display);

    // Extract the part of the render array we need.
    $output = isset($elements[0]) ? $elements[0] : array();
    if (isset($elements['#access'])) {
      $output['#access'] = $elements['#access'];
    }

    return $output;
  }

  /*
   * Returns TRUE if the view mode is cacheable.
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
    $view_modes_info = entity_get_view_modes($this->entityTypeId);
    return !empty($view_modes_info[$view_mode]['cache']);
  }

}
