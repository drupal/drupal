<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\BlocksRoles.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the blocks_roles table.
 */
class BlocksRoles extends DrupalDumpBase {

  public function load() {
    $this->createTable("blocks_roles", array(
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
    ));
    $this->database->insert("blocks_roles")->fields(array(
      'module',
      'delta',
      'rid',
    ))
    ->values(array(
      'module' => 'user',
      'delta' => '2',
      'rid' => '2',
    ))->values(array(
      'module' => 'user',
      'delta' => '3',
      'rid' => '3',
    ))->execute();
  }

}
#29243885f79abad280834034dca71856
