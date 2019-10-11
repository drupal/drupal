<?php

namespace Drupal\path\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

@trigger_error('UrlAlias is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the entity:path_alias destination instead. See https://www.drupal.org/node/3013865', E_USER_DEPRECATED);

/**
 * Legacy destination class for non-entity path aliases.
 *
 * @MigrateDestination(
 *   id = "url_alias"
 * )
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use
 * the entity:path_alias destination instead.
 *
 * @see https://www.drupal.org/node/3013865
 */
class UrlAlias extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if ($row->getDestinationProperty('source')) {
      $row->setDestinationProperty('path', $row->getDestinationProperty('source'));
    }
    $path = $row->getDestinationProperty('path');

    // Check if this alias is for a node and if that node is a translation.
    if (preg_match('/^\/node\/\d+$/', $path) && $row->hasDestinationProperty('node_translation')) {

      // Replace the alias source with the translation source path.
      $node_translation = $row->getDestinationProperty('node_translation');
      $row->setDestinationProperty('path', '/node/' . $node_translation[0]);
      $row->setDestinationProperty('langcode', $node_translation[1]);
    }

    return parent::import($row, $old_destination_id_values);
  }

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'path_alias';
  }

}
