<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * Provides entity view mode destination plugin.
 *
 * See EntityConfigBase for the available configuration options.
 * @see \Drupal\migrate\Plugin\migrate\destination\EntityConfigBase
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d7_view_mode
 * process:
 *   mode: view_mode
 *   label: view_mode
 *   targetEntityType: entity_type
 * destination:
 *   plugin: entity:entity_view_mode
 * @endcode
 *
 * This will add the results of the process ("mode", "label" and
 * "targetEntityType") to an "entity_view_mode" entity.
 *
 * @MigrateDestination(
 *   id = "entity:entity_view_mode"
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

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $destination_identifier = implode('.', $destination_identifier);
    parent::rollback([$destination_identifier]);
  }

}
