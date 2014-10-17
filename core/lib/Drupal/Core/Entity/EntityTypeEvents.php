<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityTypeEvents.
 */

namespace Drupal\Core\Entity;

/**
 * Contains all events thrown while handling entity types.
 */
final class EntityTypeEvents {

  /**
   * Event name for entity type creation.
   *
   * @var string
   */
  const CREATE = 'entity_type.definition.create';

  /**
   * Event name for entity type update.
   *
   * @var string
   */
  const UPDATE = 'entity_type.definition.update';

  /**
   * Event name for entity type deletion.
   *
   * @var string
   */
  const DELETE = 'entity_type.definition.delete';

}
