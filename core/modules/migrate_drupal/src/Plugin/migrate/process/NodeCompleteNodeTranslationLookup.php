<?php

namespace Drupal\migrate_drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns nid and langcode from migration_lookup on node_complete migration.
 *
 * It is possible that migration_lookups that use the classic node migrations
 * in the migration key have been altered to include the complete node
 * migration. The classic node migration and complete node migration have a
 * different number of destination keys. This process plugin will ensure that
 * when the complete node migration is used in the lookup the nid and langcode
 * values are returned. This keeps the behavior the same as the classic node
 * migration.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "node_complete_node_translation_lookup"
 * )
 */
class NodeCompleteNodeTranslationLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value) && count($value) === 3) {
      unset($value[1]);
      return array_values($value);
    }
    return $value;
  }

}
