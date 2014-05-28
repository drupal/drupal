<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityViewMode.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * @MigrateDestination(
 *   id = "entity:view_mode"
 * )
 */
class EntityViewMode extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['targetEntityType']['type'] = 'string';
    $ids['mode']['type'] = 'string';
    return $ids;
  }

}
