<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\EntityRenderController.
 */

namespace Drupal\Core\Entity;

/**
 * Base class for entity view controllers.
 */
class EntityRenderController implements EntityRenderControllerInterface {

  /**
   * The type of entities for which this controller is instantiated.
   *
   * @var string
   */
  protected $entityType;

  public function __construct($entity_type) {
    $this->entityType = $entity_type;
  }

  /**
   * Implements Drupal\Core\Entity\EntityRenderControllerInterface::buildContent().
   */
  public function buildContent(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    // Allow modules to change the view mode.
    $context = array('langcode' => $langcode);

    $view_modes = array();
    $displays = array();

    foreach ($entities as $entity) {
      // Remove previously built content, if exists.
      $entity->content = array();

      drupal_alter('entity_view_mode', $view_mode, $entity, $context);
      $entity->content['#view_mode'] = $view_mode;
      $view_modes[$view_mode][$entity->id()] = $entity;

      $bundle = $entity->bundle();

      // Load the corresponding display settings if not stored yet.
      if (!isset($displays[$view_mode][$bundle])) {
        // Get the display object to use for rendering the entity..
        $display = entity_get_render_display($entity, $view_mode);

        // Let modules alter the display.
        // Note: if config entities get a static cache at some point, the
        // objects should be cloned before running drupal_alter().
        $display_context = array(
          'entity_type' => $this->entityType,
          'bundle' => $bundle,
          'view_mode' => $view_mode,
        );
        drupal_alter('entity_display', $display, $display_context);

        $displays[$view_mode][$bundle] = $display;
      }

      // Assigning weights to 'extra fields' is done in a pre_render callback.
      $entity->content['#pre_render'] = array('_entity_view_pre_render');
      $entity->content['#entity_display'] = $displays[$view_mode][$bundle];
    }

    // Prepare and build field content, grouped by view mode.
    foreach ($view_modes as $view_mode => $view_mode_entities) {
      field_attach_prepare_view($this->entityType, $view_mode_entities, $displays[$view_mode], $langcode);
      module_invoke_all('entity_prepare_view', $view_mode_entities, $this->entityType);

      foreach ($view_mode_entities as $entity) {
        $entity->content += field_attach_view($entity, $displays[$view_mode][$entity->bundle()], $langcode);
      }
    }
  }

  /**
   * Provides entity-specific defaults to the build process.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
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
    return $return;
  }

  /**
   * Specific per-entity building.
   *
   * @param array $build
   *   The render array that is being created.
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be prepared.
   * @param string $view_mode
   *   The view mode that should be used to prepare the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be prepared, defaults to
   *   the current content language.
   */
  protected function alterBuild(array &$build, EntityInterface $entity, $view_mode, $langcode = NULL) { }

  /**
   * Implements Drupal\Core\Entity\EntityRenderControllerInterface::view().
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $buildList = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return $buildList[0];
  }

  /**
   * Implements Drupal\Core\Entity\EntityRenderControllerInterface::viewMultiple().
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    if (!isset($langcode)) {
      $langcode = language(LANGUAGE_TYPE_CONTENT)->langcode;
    }
    $this->buildContent($entities, $view_mode, $langcode);

    $view_hook = "{$this->entityType}_view";
    $build = array('#sorted' => TRUE);
    $weight = 0;
    foreach ($entities as $key => $entity) {
      $entity_view_mode = isset($entity->content['#view_mode']) ? $entity->content['#view_mode'] : $view_mode;
      module_invoke_all($view_hook, $entity, $entity_view_mode, $langcode);
      module_invoke_all('entity_view', $entity, $entity_view_mode, $langcode);

      $build[$key] = $entity->content;
      // We don't need duplicate rendering info in $entity->content.
      unset($entity->content);

      $build[$key] += $this->getBuildDefaults($entity, $entity_view_mode, $langcode);
      $this->alterBuild($build[$key], $entity, $entity_view_mode, $langcode);
      $build[$key]['#weight'] = $weight++;

      // Allow modules to modify the structured entity.
      drupal_alter(array($view_hook, 'entity_view'), $build[$key], $entity);
    }

    return $build;
  }
}
