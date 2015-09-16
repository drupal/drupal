<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\ShortcutSet.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the shortcut_set table.
 */
class ShortcutSet extends DrupalDumpBase {

  public function load() {
    $this->createTable("shortcut_set", array(
      'primary key' => array(
        'set_name',
      ),
      'fields' => array(
        'set_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("shortcut_set")->fields(array(
      'set_name',
      'title',
    ))
    ->values(array(
      'set_name' => 'shortcut-set-1',
      'title' => 'Default',
    ))->values(array(
      'set_name' => 'shortcut-set-2',
      'title' => 'Alternative shortcut set',
    ))->execute();
  }

}
#65b4e1b8c85fc2c4232ce4bdb50a15ab
