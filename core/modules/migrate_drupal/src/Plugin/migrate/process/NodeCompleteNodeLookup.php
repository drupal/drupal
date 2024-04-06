<?php

namespace Drupal\migrate_drupal\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns only the nid from migration_lookup on node_complete migration.
 *
 * It is possible that migration_lookups that use the classic node migrations
 * in the migration key have been altered to include the complete node
 * migration. The classic node migration and complete node migration have a
 * different number of destination keys. This process plugin will ensure that
 * when the complete node migration is used in the lookup the nid value is
 * returned. This keeps the behavior the same as the classic node migration.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('node_complete_node_lookup')]
class NodeCompleteNodeLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value) && count($value) === 3) {
      return $value[0];
    }
    return $value;
  }

}
