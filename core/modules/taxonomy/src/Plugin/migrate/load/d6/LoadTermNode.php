<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\migrate\load\d6\LoadTermNode.
 */

namespace Drupal\taxonomy\Plugin\migrate\load\d6;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\load\LoadEntity;

/**
 * @PluginID("d6_term_node")
 */
class LoadTermNode extends LoadEntity {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration) {
    $configuration['bundle_migration'] = 'd6_taxonomy_vocabulary';
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(EntityStorageInterface $storage, array $sub_ids = NULL) {
    /** @var \Drupal\migrate\Entity\MigrationInterface $bundle_migration */
    $bundle_migration = $storage->load('d6_taxonomy_vocabulary');
    $migrate_executable = new MigrateExecutable($bundle_migration, new MigrateMessage());
    $process = array_intersect_key($bundle_migration->get('process'), $bundle_migration->getDestinationPlugin()->getIds());
    $migrations = array();
    $vid_map = array();
    foreach ($bundle_migration->getIdMap() as $key => $value) {
      $old_vid = unserialize($key)['sourceid1'];
      $new_vid = $value['destid1'];
      $vid_map[$old_vid] = $new_vid;
    }
    foreach ($bundle_migration->getSourcePlugin()->getIterator() as $source_row) {
      $row = new Row($source_row, $source_row);
      $migrate_executable->processRow($row, $process);
      $old_vid = $source_row['vid'];
      $new_vid = $row->getDestinationProperty('vid');
      $vid_map[$old_vid] = $new_vid;
    }
    foreach ($vid_map as $old_vid => $new_vid) {
      $values = $this->migration->toArray();
      $migration_id = $this->migration->id() . ':' . $old_vid;
      $values['id'] = $migration_id;
      $values['source']['vid'] = $old_vid;
      $values['process'][$new_vid] = 'tid';
      $migrations[$migration_id] = $storage->create($values);;
    }

    return $migrations;
  }

}
