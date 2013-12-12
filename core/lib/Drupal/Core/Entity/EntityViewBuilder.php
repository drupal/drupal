<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityViewBuilder.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity view controllers.
 */
class EntityViewBuilder implements EntityControllerInterface, EntityViewBuilderInterface {

  /**
   * The type of entities for which this controller is instantiated.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity info array.
   *
   * @var array
   *
   * @see entity_get_info()
   */
  protected $entityInfo;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * An array of view mode info for the type of entities for which this
   * controller is instantiated.
   *
   * @var array
   */
  protected $viewModesInfo;

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
   * Constructs a new EntityViewBuilder.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $entity_info
   *   The entity information array.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   */
  public function __construct($entity_type, array $entity_info, EntityManager $entity_manager) {
    $this->entityType = $entity_type;
    $this->entityInfo = $entity_info;
    $this->entityManager = $entity_manager;
    $this->viewModesInfo = entity_get_view_modes($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static($entity_type, $entity_info, $container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    field_attach_prepare_view($this->entityType, $entities, $displays, $langcode);

    // Initialize the field item attributes for the fields set to be displayed.
    foreach ($entities as $entity) {
      // The entity can include fields that aren't displayed, and the display
      // can include components that aren't fields, so we want to iterate the
      // intersection of $entity->getProperties() and $display->getComponents().
      // However, the entity can have many more fields than are displayed, so we
      // avoid the cost of calling $entity->getProperties() by iterating the
      // intersection as follows.
      foreach ($displays[$entity->bundle()]->getComponents() as $name => $options) {
        if ($entity->hasField($name)) {
          foreach ($entity->get($name) as $item) {
            $item->_attributes = array();
          }
        }
      }
    }

    module_invoke_all('entity_prepare_view', $this->entityType, $entities, $displays, $view_mode);

    foreach ($entities as $entity) {
      // Remove previously built content, if exists.
      $entity->content = array(
        '#view_mode' => $view_mode,
      );
      $entity->content += field_attach_view($entity, $displays[$entity->bundle()], $langcode);
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
      '#theme' => $this->entityType,
      "#{$this->entityType}" => $entity,
      '#view_mode' => $view_mode,
      '#langcode' => $langcode,
    );

    // Cache the rendered output if permitted by the view mode and global entity
    // type configuration. The isset() checks below are necessary because
    // 'default' is not an actual view mode.
    $view_mode_is_cacheable = !isset($this->viewModesInfo[$view_mode]) || (isset($this->viewModesInfo[$view_mode]) && $this->viewModesInfo[$view_mode]['cache']);
    if ($view_mode_is_cacheable && !$entity->isNew() && !isset($entity->in_preview) && $this->entityInfo['render_cache']) {
      $return['#cache'] = array(
        'keys' => array('entity_view', $this->entityType, $entity->id(), $view_mode),
        'granularity' => DRUPAL_CACHE_PER_ROLE,
        'bin' => $this->cacheBin,
        'tags' => array(
          $this->entityType . '_view' => TRUE,
          $this->entityType => array($entity->id()),
        ),
      );
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
   *   The entity_display object holding the display options configured for
   *   the entity components.
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
      $langcode = language(Language::TYPE_CONTENT)->id;
    }

    // Build the view modes and display objects.
    $view_modes = array();
    $displays = array();
    $context = array('langcode' => $langcode);
    foreach ($entities as $key => $entity) {
      $bundle = $entity->bundle();

      // Ensure that from now on we are dealing with the proper translation
      // object.
      $entity = $this->entityManager->getTranslationFromContext($entity, $langcode);
      $entities[$key] = $entity;

      // Allow modules to change the view mode.
      $entity_view_mode = $view_mode;
      drupal_alter('entity_view_mode', $entity_view_mode, $entity, $context);
      // Store entities for rendering by view_mode.
      $view_modes[$entity_view_mode][$entity->id()] = $entity;

      // Get the corresponding display settings.
      if (!isset($displays[$entity_view_mode][$bundle])) {
        $displays[$entity_view_mode][$bundle] = entity_get_render_display($entity, $entity_view_mode);
      }
    }

    foreach ($view_modes as $mode => $view_mode_entities) {
      $this->buildContent($view_mode_entities, $displays[$mode], $mode, $langcode);
    }

    $view_hook = "{$this->entityType}_view";
    $build = array('#sorted' => TRUE);
    $weight = 0;
    foreach ($entities as $key => $entity) {
      $entity_view_mode = isset($entity->content['#view_mode']) ? $entity->content['#view_mode'] : $view_mode;
      $display = $displays[$entity_view_mode][$entity->bundle()];
      module_invoke_all($view_hook, $entity, $display, $entity_view_mode, $langcode);
      module_invoke_all('entity_view', $entity, $display, $entity_view_mode, $langcode);

      $build[$key] = $entity->content;
      // We don't need duplicate rendering info in $entity->content.
      unset($entity->content);

      $build[$key] += $this->getBuildDefaults($entity, $entity_view_mode, $langcode);
      $this->alterBuild($build[$key], $entity, $display, $entity_view_mode, $langcode);

      // Assign the weights configured in the display.
      foreach ($display->getComponents() as $name => $options) {
        if (isset($build[$key][$name])) {
          $build[$key][$name]['#weight'] = $options['weight'];
        }
      }

      $build[$key]['#weight'] = $weight++;

      // Allow modules to modify the render array.
      drupal_alter(array($view_hook, 'entity_view'), $build[$key], $entity, $display);
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
        $tags[$this->entityType][$id] = $id;
        $tags[$this->entityType . '_view_' . $entity->bundle()] = TRUE;
      }
      \Drupal::cache($this->cacheBin)->deleteTags($tags);
    }
    else {
      \Drupal::cache($this->cacheBin)->deleteTags(array($this->entityType . '_view' => TRUE));
    }
  }
}
