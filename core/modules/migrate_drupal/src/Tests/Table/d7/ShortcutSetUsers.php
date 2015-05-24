<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\ShortcutSetUsers.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the shortcut_set_users table.
 */
class ShortcutSetUsers extends DrupalDumpBase {

  public function load() {
    $this->createTable("shortcut_set_users", array(
      'primary key' => array(
        'uid',
      ),
      'fields' => array(
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'set_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("shortcut_set_users")->fields(array(
      'uid',
      'set_name',
    ))
    ->execute();
  }

}
#29056f57d584c37c6a0691aa5b5a0465
