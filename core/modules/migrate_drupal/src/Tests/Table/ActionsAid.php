<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\ActionsAid.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the actions_aid table.
 */
class ActionsAid extends Drupal6DumpBase {

  public function load() {
    $this->createTable("actions_aid", array(
      'primary key' => array(
        'aid',
      ),
      'fields' => array(
        'aid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("actions_aid")->fields(array(
      'aid',
    ))
    ->execute();
  }

}
