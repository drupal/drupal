<?php

namespace Drupal\Core\Entity;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fired when entity list builder is about to add a row to the render array.
 */
class EntityListBuilderRowEvent extends Event {

  /**
   * The row render array.
   *
   * @var array
   */
  protected $row;

  /**
   * The row underlying entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a new event instance.
   *
   * @param array $row
   *   The row render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The row underlying entity.
   */
  public function __construct(array $row, EntityInterface $entity) {
    $this->row = $row;
    $this->entity = $entity;
  }

  /**
   * Sets the row render array.
   *
   * @param array $row
   *   The row render array.
   *
   * @return $this
   */
  public function setRow(array $row): self {
    $this->row = $row;
    return $this;
  }

  /**
   * Returns the row render array.
   *
   * @return array
   *   The row render array.
   */
  public function getRow(): array {
    return $this->row;
  }

  /**
   * Returns the row underlying entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The row underlying entity.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
