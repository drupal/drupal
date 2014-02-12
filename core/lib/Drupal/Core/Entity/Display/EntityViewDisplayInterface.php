<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Display\EntityViewDisplayInterface.
 */

namespace Drupal\Core\Entity\Display;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a common interface for entity view displays.
 */
interface EntityViewDisplayInterface extends EntityDisplayInterface {

  /**
   * Returns a renderable array for the components of an entity.
   *
   * See the buildMultiple() method for details.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being displayed.
   *
   * @return array
   *   A renderable array for the entity.
   *
   * @see \Drupal\Core\Entity\Display\EntityViewDisplayInterface::buildMultiple()
   */
  public function build(ContentEntityInterface $entity);

  /**
   * Returns a renderable array for the components of a set of entities.
   *
   * This only includes the components handled by the Display object, but
   * excludes 'extra fields', that are typically rendered through specific,
   * ad-hoc code in EntityViewBuilderInterface::buildContent() or in
   * hook_entity_view() implementations.
   *
   * hook_entity_display_build_alter() is invoked on each entity, allowing 3rd
   * party code to alter the render array.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The entities being displayed.
   *
   * @return array
   *   A renderable array for the entities, indexed by the same keys as the
   *   $entities array parameter.
   *
   * @see hook_entity_display_build_alter()
   */
  public function buildMultiple(array $entities);

}
