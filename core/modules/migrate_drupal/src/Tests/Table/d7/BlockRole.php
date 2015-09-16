<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\BlockRole.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the block_role table.
 */
class BlockRole extends DrupalDumpBase {

  public function load() {
    $this->createTable("block_role", array(
      'primary key' => array(
        'module',
        'delta',
        'rid',
      ),
      'fields' => array(
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
        ),
        'delta' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
        ),
        'rid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("block_role")->fields(array(
      'module',
      'delta',
      'rid',
    ))
    ->values(array(
      'module' => 'block',
      'delta' => '1',
      'rid' => '2',
    ))->execute();
  }

}
#0280c4568cc18ce4b7e173c046a61a05
