<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a migrate map save event for event listeners.
 */
class MigrateMapSaveEvent extends Event {

  /**
   * Constructs a migration map event object.
   *
   * @param \Drupal\migrate\Plugin\MigrateIdMapInterface $map
   *   Map plugin.
   * @param array $fields
   *   Array of fields being saved to the map.
   */
  public function __construct(
    protected MigrateIdMapInterface $map,
    protected array $fields,
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
   * Gets the fields about to be saved to the map.
   *
   * @return array
   *   Array of map fields, keyed by field name.
   */
  public function getFields() {
    return $this->fields;
  }

}
