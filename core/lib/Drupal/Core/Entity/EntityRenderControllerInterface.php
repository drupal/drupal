<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityRenderControllerInterface.
 */

namespace Drupal\Core\Entity;

/**
 * Defines a common interface for entity view controller classes.
 */
interface EntityRenderControllerInterface {

  /**
   * Build the structured $content property on the entity.
   *
   * @param array $entities
   *   The entities, implementing EntityInterface, whose content is being built.
   * @param array $displays
   *   The array of entity_display objects holding the display options
   *   configured for the entity components, keyed by bundle name.
   * @param string $view_mode
   *   The view mode in which the entity is being viewed.
   * @param string $langcode
   *   (optional) For which language the entity should be build, defaults to
   *   the current content language.
   *
   * @return array
   *   The content array.
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL);

  /**
   * Returns the render array for the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array for the entity.
   *
   * @throws \InvalidArgumentException
   *   Can be thrown when the set of parameters is inconsistent, like when
   *   trying to view a Comment and passing a Node which is not the one the
   *   comment belongs to, or not passing one, and having the comment node not
   *   be available for loading.
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL);

  /**
   * Returns the render array for the provided entities.
   *
   * @param array $entities
   *   An array of entities implementing EntityInterface to view.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return
   *   A render array for the entities, indexed by the same keys as the
   *   entities array passed in $entities.
   *
   * @throws \InvalidArgumentException
   *   Can be thrown when the set of parameters is inconsistent, like when
   *   trying to view Comments and passing a Node which is not the one the
   *   comments belongs to, or not passing one, and having the comments node not
   *   be available for loading.
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL);

  /**
   * Resets the entity render cache.
   *
   * @param array|null $ids
   *   (optional) If specified, the cache is reset for the given entity IDs
   *   only.
   */
  public function resetCache(array $ids = NULL);

}
