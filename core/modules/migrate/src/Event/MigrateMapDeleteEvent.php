<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a migrate map delete event for event listeners.
 */
class MigrateMapDeleteEvent extends Event {

  /**
   * Constructs a migration map delete event object.
   *
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $map
   *   Map plugin.
   * @param array $sourceId
   *   Array of source ID fields representing the object being deleted from the
   *   map.
   */
  public function __construct(
    protected MigrateIdMapInterface $map,
    protected array $sourceId,
  ) {
  }

  /**
   * Gets the map plugin.
   *
   * @return \Drupal\migrate\Plugin\MigrateIdMapInterface
   *   The map plugin that caused the event to fire.
   */
  public function getMap() {
    return $this->map;
  }

  /**
   * Gets the source ID of the item being removed from the map.
   *
   * @return array
   *   Array of source ID fields.
   */
  public function getSourceId() {
    return $this->sourceId;
  }

}
