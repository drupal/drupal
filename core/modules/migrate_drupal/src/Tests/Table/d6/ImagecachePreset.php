<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ImagecachePreset.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the imagecache_preset table.
 */
class ImagecachePreset extends DrupalDumpBase {

  public function load() {
    $this->createTable("imagecache_preset", array(
      'primary key' => array(
        'presetid',
      ),
      'fields' => array(
        'presetid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'presetname' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("imagecache_preset")->fields(array(
      'presetid',
      'presetname',
    ))
    ->values(array(
      'presetid' => '1',
      'presetname' => 'slackjaw_boys',
    ))->values(array(
      'presetid' => '2',
      'presetname' => 'big_blue_cheese',
    ))->execute();
  }

}
#b2102d82ad5b3d8be026fe23cea75674
