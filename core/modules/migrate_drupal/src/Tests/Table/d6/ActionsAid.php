<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ActionsAid.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the actions_aid table.
 */
class ActionsAid extends DrupalDumpBase {

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
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("actions_aid")->fields(array(
      'aid',
    ))
    ->execute();
  }

}
#a58e383f81c657ac36eabc980e148fbd
