<?php

namespace Drupal\Core\Entity;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Defines a base class for all entity type events.
 */
class EntityTypeEvent extends GenericEvent {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The original entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $original;

  /**
   * Constructs a new EntityTypeEvent.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The field storage definition.
   * @param \Drupal\Core\Entity\EntityTypeInterface $original
   *   (optional) The original entity type. This should be passed only when
   *   updating the entity type.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeInterface $original = NULL) {
    $this->entityType = $entity_type;
    $this->original = $original;
  }

  /**
   * The entity type the event refers to.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * The original entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   */
  public function getOriginal() {
    return $this->original;
  }

}
