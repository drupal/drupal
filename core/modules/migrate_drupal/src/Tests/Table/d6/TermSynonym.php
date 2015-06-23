<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\TermSynonym.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the term_synonym table.
 */
class TermSynonym extends DrupalDumpBase {

  public function load() {
    $this->createTable("term_synonym", array(
      'primary key' => array(
        'tsid',
      ),
      'fields' => array(
        'tsid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'tid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("term_synonym")->fields(array(
      'tsid',
      'tid',
      'name',
    ))
    ->execute();
  }

}
#f872b9f69bd357799c9aebbfc65dd736
